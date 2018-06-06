<?php


namespace Denismitr\LTP\Traits;


use Denismitr\LTP\Exceptions\PermissionDoesNotExist;
use Denismitr\LTP\Guard;
use Denismitr\LTP\Models\Permission;
use Denismitr\LTP\PermissionLoader;
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
     * @throws \ReflectionException
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName(
                $permission,
                $this->getDefaultGuard()
            );
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
     * @throws \ReflectionException
     */
    public function hasDirectPermission($permission): bool
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission, $this->getDefaultGuard());
            if (! $permission) {
                return false;
            }
        }

        if (is_int($permission)) {
            $permission = app(Permission::class)->findById($permission, $this->getDefaultGuard());
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
     * @throws \ReflectionException
     */
    protected function getPermission($permission): Permission
    {
        if (is_numeric($permission)) {
            return app(Permission::class)->findById($permission, $this->getDefaultGuard());
        }

        if (is_string($permission)) {
            return app(Permission::class)->findByName($permission, $this->getDefaultGuard());
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
     * @throws \ReflectionException
     */
    public function getPermissions(array $permissions)
    {
        if (! empty($permissions) ) {
            if (is_string($permissions[0])) {
                return app(Permission::class)
                    ->whereIn('name', $permissions)
                    ->whereIn('guard', $this->getGuards())
                    ->get();
            }

            if ($permissions[0] instanceof Permission) {
                return $permissions;
            }
        }

        throw new PermissionDoesNotExist("Permissions list is invalid");
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \ReflectionException
     */
    protected function getGuards()
    {
        return Guard::getNames($this);
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    protected function getDefaultGuard(): string
    {
        return Guard::getDefault($this);
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

            return Permission::findByName($permission, $this->getDefaultGuard());
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
     * @throws \ReflectionException
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
            })
            ->each(function($permission) {
                Guard::verifyIsSharedBetween($permission, $this);
            })
            ->all();

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