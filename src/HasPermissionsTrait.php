<?php

namespace Denismitr\Permissions;

use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\Role;

trait HasPermissionsTrait
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'users_roles');
    }
}