<?php

namespace Denismitr\Permissions;

use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\Role;

trait HasRolesAndPermissions
{
    /**
     * Check if the user has a given permission
     *
     * @param  Denismitr\Permissions\Models\Permission|string  $permission
     * @return bool
     */
    public function hasPermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $name);
        }

        if (! $permission) {
            return false;
        }

        return $this->hasPermissionThroughRole($permission) || $this->hasPermission($permission);
    }

    /**
     * Give the user a certain permission|s
     * @param  string $permissions
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = $this->getAllPermissions(array_flatten($permissions));

        if ($permissions === null) {
            return $this;
        }

        foreach ($permissions as $key => $permission) {
            if ($this->hasPermissionTo($permission)) {
                unset($permissions[$key]);
            }
        }

        $this->permissions()->saveMany($permissions);

        return $this;
    }

    /**
     * Give all permissions to user
     *
     * @return $this
     */
    public function grantAllPermissions()
    {
        $permission = new Permission;
        $permission->name = 'all';

        $this->permissions()->saveMany([$permission]);

        return $this;
    }


    public function withdrawPermissionTo(...$permissions)
    {
        $permissions = $this->getAllPermissions(array_flatten($permissions));

        $this->permissions()->detach($permissions);
    }

    public function updatePermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Give a role to the user
     *
     * @param  string $roles
     * @return $this
     */
    public function assignRole(...$roles)
    {
        foreach ($roles as $role) {
            if ( ! $this->hasRole($role) ) {
                $this->roles()->create([
                    'name' => $role
                ]);
            }
        }

        return $this;
    }

    /**
     * Alias for hasRole
     *
     * @param  string $roles
     * @return bool
     */
    public function is(...$roles)
    {
        return $this->hasRole(...$roles);
    }

    /**
     * Check if user has role
     *
     * @param  string $roles
     * @return bool
     */
    public function hasRole(...$roles)
    {
        foreach ($roles as $role) {
            if ($this->roles->contains('name', strtolower($role))) {
                return true;
            }
        }

        return false;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }

    protected function hasPermissionThroughRole(Permission $permission)
    {
        foreach ($permission->roles as $role) {
            if ($this->roles->contains($role)) {
                return true;
            }
        }

        return false;
    }

    protected function hasPermission(Permission $permission)
    {
        return (bool) $this->permissions->where('name', $permission->name)->count();
    }

    protected function getAllPermissions(array $permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }
}