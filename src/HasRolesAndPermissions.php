<?php

namespace Denismitr\LTP;

use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasRolesAndPermissions
{
    /**
     * @param array ...$permissions
     * @return bool
     */
    public function hasPermissionTo(...$permissions)
    {
        foreach ($permissions as $name) {
            if ( ! $this->hasPermissionThroughRole($name) && ! $this->hasPermission($name)) {
                return false;
            }
        }

        return true;
    }

    public function can($ability, $arguments = [])
    {
        if ($this->hasPermissionTo($ability)) {
            return true;
        }

        return parent::can($ability, $arguments);
    }

    /**
     * @param array ...$permissions
     * @return $this
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     */
    public function givePermissionTo(...$permissions)
    {
        foreach ($permissions as $key => $permission) {
            if ($this->hasPermissionTo($permission)) {
                unset($permissions[$key]);
            } else {
                $permissions[$key] = Permission::findByName($permission);
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

    /**
     * @param array ...$permissions
     * @return $this
     */
    public function withdrawPermissionTo(...$permissions)
    {
        $permissions = $this->getAllPermissions(array_flatten($permissions));

        $this->permissions()->detach($permissions);

        $this->load('permissions');

        return $this;
    }

    /**
     * @param array ...$permissions
     * @return $this
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     */
    public function updatePermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo(...$permissions);
    }

    /**
     * @param array ...$roles
     * @return $this
     * @throws \Denismitr\Permissions\Exceptions\RoleDoesNotExist
     */
    public function assignRole(...$roles)
    {
        foreach ($roles as $role) {
            if ( ! $this->hasRole($role) ) {
                $role = Role::findByName($role);

                $this->roles()->attach($role);

                $this->load('roles');
            }
        }

        return $this;
    }

    /**
     * @param array ...$roles
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    /**
     *  Get permissions for user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }

    /**
     * @param string $name
     * @return bool
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     */
    protected function hasPermissionThroughRole(string $name)
    {
        $permission = Permission::findByName($name);

        if (! $permission) {
            return false;
        }

        foreach ($permission->roles as $role) {
            if ($this->roles->contains($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * See if user has permission by name
     *
     * @param  string  $name
     * @return bool
     */
    protected function hasPermission(string $name)
    {
        return (bool) $this->permissions->where('name', $name)->count();
    }

    /**
     * @param array $permissions
     * @return mixed
     */
    protected function getAllPermissions(array $permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }
}