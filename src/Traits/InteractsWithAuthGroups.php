<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Exceptions\UserCannotOwnAuthGroups;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Models\AuthGroupUser;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\PermissionLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait InteractsWithAuthGroups
{
    public static function bootBelongsToAuthGroup()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->authGroups()->detach();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * @param Builder $query
     * @param $permissions
     * @return Builder
     */
    public function scopeWithPermissions(Builder $query, $permissions): Builder
    {
        $permissions = $this->resolvePermissions($permissions);

        $rolesWithPermissions = $permissions->map(function($permission) {
            return $permission->groups->all();
        })->flatten()->unique();

        return $query->where(function ($query) use ($permissions, $rolesWithPermissions) {
            $query->whereHas('authGroupUsers.permissions', function ($query) use ($permissions) {

                foreach ($permissions as $permission) {
                    $query->where(
                        config('permissions.tables.permissions').'.id',
                        $permission->id
                    );
                }
            });

            if ($rolesWithPermissions->count() > 0) {
                $query->orWhereHas('authGroups', function ($query) use ($rolesWithPermissions) {
                    $query->where(function ($query) use ($rolesWithPermissions) {
                        foreach ($rolesWithPermissions as $role) {
                            $query->orWhere(
                                config('permissions.tables.auth_groups').'.id',
                                $role->id
                            );
                        }
                    });
                });
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return mixed
     */
    public function ownedAuthGroups(): HasMany
    {
        return $this->hasMany(config('permissions.models.auth_group'), 'owner_id');
    }

    /**
     * @return mixed
     */
    public function authGroupUsers()
    {
        return $this->hasMany(AuthGroupUser::class, 'user_id');
    }

    /**
     * @param $authGroup
     * @return AuthGroupUser
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupUserNotFound
     */
    public function onAuthGroup($authGroup): AuthGroupUser
    {
        return AuthGroupUser::findByAuthGroupAndUser($authGroup, $this);
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    /**
     * @param array $attributes
     * @return AuthGroup
     * @throws UserCannotOwnAuthGroups
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists
     */
    public function createNewAuthGroup(array $attributes = []): AuthGroup
    {
        if ($this->canOwnAuthGroups()) {
            return $this->switchToAuthGroup(
                AuthGroup::create(
                    array_merge($attributes, [
                        'owner_id' => $this->id
                    ])
                )
            );
        }

        throw new UserCannotOwnAuthGroups;
    }

    /**
     * @param $authGroup
     * @param array ...$permissions
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupUserNotFound
     */
    public function grantPermissionsOnAuthGroup($authGroup, ...$permissions)
    {
        /** @var AuthGroupUser $authGroupUser */
        $authGroupUser = AuthGroupUser::findByAuthGroupAndUser($authGroup, $this);

        $authGroupUser->grantPermissionTo(...$permissions);
    }

    /**
     * @param $authGroup
     * @param $permission
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupUserNotFound
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     */
    public function revokePermissionOnAuthGroup($authGroup, $permission)
    {
        /** @var AuthGroupUser $authGroupUser */
        $authGroupUser = AuthGroupUser::findByAuthGroupAndUser($authGroup, $this);

        $authGroupUser->revokePermissionTo($permission);
    }

    public function switchToAuthGroup(AuthGroup $authGroup): AuthGroup
    {
        $this->current_auth_group_id = $authGroup->id;

        $this->save();

        return $authGroup;
    }

    /**
     * @param array ...$groups
     * @return $this
     */
    public function joinAuthGroup(...$groups)
    {
        $groups = collect($groups)
            ->flatten()
            ->map(function ($group) {
                return $this->getAuthGroup($group);
            });

        $this->authGroups()->saveMany($groups->all());

        app(PermissionLoader::class)->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param array ...$groups
     * @return $this
     */
    public function syncAuthGroup(...$groups)
    {
        $this->authGroups()->detach();

        return $this->joinAuthGroup($groups);
    }

    /**
     * Refresh the current auth group for the user.
     *
     * @return AuthGroup
     */
    public function refreshCurrentAuthGroup(): AuthGroup
    {
        $this->current_auth_group_id = null;

        $this->save();

        return $this->currentAuthGroup();
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    public function canOwnAuthGroups(): bool
    {
        return true;
    }

    /**
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission);
        }

        return $this->hasPermissionThroughAuthGroup($permission);
    }

    /**
     * @param $permission
     * @return bool
     */
    public function isAllowedTo($permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    /**
     * @param $permission
     * @return bool
     */
    public function hasPermissionThroughAuthGroup(Permission $permission): bool
    {
        return $this->isOneOf($permission->groups) || $permission->isGrantedFor($this);
    }

    /**
     * @param $groups
     * @return bool
     */
    public function isOneOf($groups): bool
    {
        return $this->isOneOfAny($groups);
    }

    /**
     * @param $groups
     * @return bool
     */
    public function isOneOfAny($groups): bool
    {
        if (is_string($groups) && (false !== strpos($groups, '|') || false !== strpos($groups, ','))) {
            $groups = $this->convertPipeToArray($groups);
        }

        if (is_string($groups)) {
            return $this->belongsToOrOwnsAuthGroupBy('name', $groups);
        }

        if ($groups instanceof AuthGroup) {
            return $this->belongsToOrOwnsAuthGroupBy('id', $groups->id);
        }

        if (is_array($groups)) {
            foreach ($groups as $group) {
                if ($this->isOneOf($group)) {
                    return true;
                }
            }

            return false;
        }

        return $groups->intersect($this->authGroups)->isNotEmpty();
    }

    /**
     * @param $groups
     * @return bool
     */
    public function isOneOfAll($groups): bool
    {
        if (is_string($groups) && false !== strpos($groups, '|')) {
            $groups = $this->convertPipeToArray($groups);
        }

        if (is_string($groups)) {
            return $this->authGroups->contains('name', $groups);
        }

        if ($groups instanceof AuthGroup) {
            return $this->authGroups->contains('id', $groups->id);
        }

        $groups = collect()->make($groups)->map(function ($group) {
            return $group instanceof AuthGroup ? $group->name : $group;
        });

        return $groups->intersect($this->authGroups->pluck('name')) == $groups;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    protected function belongsToOrOwnsAuthGroupBy($key, $value): bool
    {
        if ($this->ownedAuthGroups->contains($key, $value)) {
            return true;
        }

        return $this->authGroups->contains($key, $value);
    }

    /**
     * Get a current auth group name
     *
     * @return string
     */
    public function currentAuthGroupName(): ?string
    {
        $currentAuthGroup = $this->currentAuthGroup();

        return is_object($currentAuthGroup) ? $currentAuthGroup->name : null;
    }

    /**
     * Determine if the user is a member of any auth group
     *
     * @return bool
     */
    public function belongsToAnyAuthGroup(): bool
    {
        return count($this->authGroups) > 0;
    }

    /**
     * Determine if the user is a part of an active auth group
     *
     * @return bool
     */
    public function onActiveAuthGroup()
    {
        $authGroup = $this->currentAuthGroup();

        if ( ! $authGroup) {
            return false;
        }

        return $authGroup->isActive();
    }

    /**
     * Get the team that user is currently viewing.
     *
     * @return AuthGroup
     */
    public function currentAuthGroup(): ?AuthGroup
    {
        if ( is_null($this->current_auth_group_id) && $this->belongsToAnyAuthGroup() ) {
            $this->switchToAuthGroup($this->authGroups()->first());

            return $this->currentAuthGroup();
        } else if ( ! is_null($this->current_auth_group_id) ) {
            $currentAuthGroup = $this->authGroups()->find($this->current_auth_group_id);

            return $currentAuthGroup ?: $this->refreshCurrentAuthGroup();
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */


    public function scopeInAuthGroups(Builder $query, $groups): Builder
    {
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }

        if (! is_array($groups)) {
            $groups = [$groups];
        }

        $groups = array_map(function ($group) {
            if ($group instanceof AuthGroup) {
                return $group;
            }

            return app(AuthGroup::class)->findByName($group);
        }, $groups);

        return $query->whereHas('authGroups', function ($query) use ($groups) {
            $query->where(function ($query) use ($groups) {
                foreach ($groups as $group) {
                    $query->orWhere(config('permission.table_names.auth_groups').'.id', $group->id);
                }
            });
        });
    }

    /**
     * @return MorphToMany
     */
    public function authGroups(): BelongsToMany
    {
        return $this->belongsToMany(AuthGroup::class, 'auth_group_users');
    }

    /**
     * @return Collection
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return Collection
     */
    public function getAuthGroupNames(): Collection
    {
        return $this->authGroups->pluck('name');
    }

    /**
     * @param $group
     * @return AuthGroup
     */
    public function getAuthGroup($group): AuthGroup
    {
        if (is_numeric($group)) {
            return app(config('permissions.models.auth_group'))->findById($group);
        }

        if (is_string($group)) {
            return app(config('permissions.models.auth_group'))->findByName($group);
        }

        return $group;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = str_replace(',', '|', trim($pipeString));

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);

        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }

    /**
     * @param $permissions
     * @return Collection
     */
    protected function resolvePermissions($permissions): Collection
    {
        if ($permissions instanceof Collection) {
            return $permissions;
        }

        $permissions = collect($permissions);

        return $permissions->map(function($permission) {
            if ($permission instanceof Permission) {
                return $permission;
            }

            return Permission::findByName($permission);
        });
    }
}