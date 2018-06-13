<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Exceptions\UserCannotOwnAuthGroups;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Models\AuthGroupUser;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\PermissionLoader;
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
     * @param $authGroup
     * @return AuthGroupUser
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupUserNotFound
     */
    public function onAuthGroup($authGroup): AuthGroupUser
    {
        $authGroup = $this->getAuthGroup($authGroup);

        return AuthGroupUser::findByAuthGroupAndUser($authGroup->id, $this->id);
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
    public function hasPermissionThroughAuthGroup($permission): bool
    {
        return $this->isOneOf($permission->groups);
    }

    /**
     * @param $groups
     * @return bool
     */
    public function isOneOf($groups): bool
    {
        if (is_string($groups) && false !== strpos($groups, '|')) {
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
    public function currentAuthGroup(): AuthGroup
    {
        if ( is_null($this->current_auth_group_id) && $this->belongsToAnyAuthGroup() ) {
            $this->switchToAuthGroup($this->authGroups()->first());

            return $this->currentAuthGroup();
        } else if ( ! is_null($this->current_auth_group_id) ) {
            $currentAuthGroup = $this->authGroups()->find($this->current_auth_group_id);

            return $currentAuthGroup ?: $this->refreshCurrentAuthGroup();
        }
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
        $pipeString = trim($pipeString);

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
}