<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\PermissionLoader;

trait HasPermissions
{
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

        app(PermissionLoader::class)->forgetCachedPermissions();

        $this->load('permissions');

        return $this;
    }

    /**
     * @param $permission
     * @return $this
     * @throws PermissionDoesNotExist
     */
    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getPermission($permission));

        app(PermissionLoader::class)->forgetCachedPermissions();

        $this->load('permissions');

        return $this;
    }

    /**
     * @param array ...$permissions
     * @return $this
     */
    public function allowTo(...$permissions)
    {
        return $this->givePermissionTo(...$permissions);
    }

    /**
     * @param array ...$permissions
     * @return $this
     */
    public function grantPermissionTo(...$permissions)
    {
        return $this->givePermissionTo(...$permissions);
    }

    /**
     * @param $permission
     * @return Permission
     * @throws PermissionDoesNotExist
     */
    protected function getPermission($permission): Permission
    {
        if (is_numeric($permission)) {
            return Permission::findById($permission);
        }

        if (is_string($permission)) {
            return app(Permission::class)->findByName($permission);
        }

        if ($permission instanceof Permission) {
            return $permission;
        }

        throw new PermissionDoesNotExist("Permission {$permission} is invalid or does not exist.");
    }
}