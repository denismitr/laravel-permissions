<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\PermissionLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait HasPermissions
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
    | Relationships and scopes
    |--------------------------------------------------------------------------
    */

    /**
     * A model may have multiple direct permissions.
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(
            config('permissions.models.permission'),
            'user',
            config('permissions.table_names.user_permissions'),
            'user_id',
            'permission_id'
        );
    }

    /**
     * @param Builder $query
     * @param $permissions
     * @return Builder
     */
    public function scopePermission(Builder $query, $permissions): Builder
    {
        $permissions = $this->resolvePermissions($permissions);

        $rolesWithPermissions = $permissions->map(function($permission) {
                return $permission->roles->all();
            })->flatten()->unique();

        return $query->where(function ($query) use ($permissions, $rolesWithPermissions) {
            $query->whereHas('permissions', function ($query) use ($permissions) {

                foreach ($permissions as $permission) {
                    $query->where(
                        config('permissions.table_names.permissions').'.id',
                        $permission->id
                    );
                }
            });

            if ($rolesWithPermissions->count() > 0) {
                $query->orWhereHas('roles', function ($query) use ($rolesWithPermissions) {
                    $query->where(function ($query) use ($rolesWithPermissions) {
                          foreach ($rolesWithPermissions as $role) {
                              $query->orWhere(
                                  config('permissions.table_names.roles').'.id',
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

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return $this->hasRole($permission->roles);
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
     * @return $this
     * @throws PermissionDoesNotExist
     */
    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getPermission($permission));

        return $this;
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
        app(PermissionLoader::class)->forgetCachedPermissions();
    }
}