<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Loader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait AuthGroupPermissions
{
    public static function bootHasPermissions()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->permissions->detach();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
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
            $query->whereHas('permissions', function ($query) use ($permissions) {

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
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission);
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaAuthGroup($permission);
    }

    protected function hasPermissionViaAuthGroup(Permission $permission): bool
    {
        return $this->isOneOf($permission->groups);
    }

    /**
     * @param $permission
     * @return bool
     */
    public function hasDirectPermission($permission): bool
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission);
            if (! $permission) {
                return false;
            }
        }

        if (is_int($permission)) {
            $permission = app(Permission::class)->findById($permission);
            if (! $permission) {
                return false;
            }
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * @param $permission
     * @return Permission
     * @throws PermissionDoesNotExist
     */
    protected function getPermission($permission): Permission
    {
        if (is_numeric($permission)) {
            return app(Permission::class)->findById($permission);
        }

        if (is_string($permission)) {
            return app(Permission::class)->findByName($permission);
        }

        if ($permission instanceof Permission) {
            return $permission;
        }

        throw new PermissionDoesNotExist("Permission {$permission} is invalid or does not exist.");
    }

    /**
     * @param array $permissions
     * @return mixed
     * @throws PermissionDoesNotExist
     */
    public function getPermissions(array $permissions)
    {
        if (! empty($permissions) ) {
            if (is_string($permissions[0])) {
                return app(Permission::class)
                    ->whereIn('name', $permissions)
                    ->get();
            }

            if ($permissions[0] instanceof Permission) {
                return $permissions;
            }
        }

        throw new PermissionDoesNotExist("Permissions list is invalid");
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

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    /**
     * @param array ...$permissions
     * @return $this
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * @param $permission
     * @return AuthGroup
     * @throws PermissionDoesNotExist
     */
    public function revokePermissionTo($permission): AuthGroup
    {
        $this->permissions()->detach($this->getPermission($permission));

        $this->forgetCachedPermissions();
        $this->load('permissions');

        return $this;
    }

    /**
     * @param $permission
     * @return AuthGroup
     * @throws PermissionDoesNotExist
     */
    public function withdrawPermissionTo($permission): AuthGroup
    {
        return $this->revokePermissionTo($permission);
    }

    /**
     * @param array ...$permissions
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                return $this->getPermission($permission);
            })->all();

        $this->permissions()->saveMany($permissions);

        $this->load('permissions');

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions()
    {
        app(Loader::class)->forgetCachedPermissions();
    }
}