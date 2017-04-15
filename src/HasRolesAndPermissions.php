<?php

namespace Denismitr\Permissions;

use App\User;
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
    public function hasPermissionTo(string $permission)
    {
        return $this->hasPermissionThroughRole($permission) || $this->hasPermission($permission);
    }

    /**
     * Give the user a certain permission|s
     * @param  string $permissions
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        foreach ($permissions as $key => $permission) {
            if ($this->hasPermissionTo($permission)) {
                unset($permissions[$key]);
            } else {
                $permissions[$key] = Permission::fromName($permission);
            }
        }

        $this->permissions()->saveMany($permissions);

        $this->load('permissions');

        return $this;
    }

    /**
     * Give all permissions to user
     *
     * @return $this
     */
    public function grantAllPermissions()
    {
        $permission = Permission::fromName('all');

        $this->permissions()->saveMany([$permission]);

        $this->load('permissions');

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
                $role = Role::fromName($role);

                $this->roles()->attach($role);

                $this->load('roles');
            }
        }

        return $this;
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

    /**
     *  Get roles of the user
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    /**
     *  Get permissions for user
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }

    protected function hasPermissionThroughRole(string $permission)
    {
        // foreach ($permission->roles as $role) {
        //     if ($this->roles->contains($role)) {
        //         return true;
        //     }
        // }

        return false;
    }

    protected function hasPermission(string $permission)
    {
        return (bool) $this->permissions->where('name', $permission)->count();
    }

    protected function getAllPermissions(array $permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }
}