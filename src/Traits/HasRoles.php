<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasRoles
{
    public function assignRole($role)
    {
        $this->roles()->attach($role);
    }

    /**
     * @return MorphToMany
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'user', 'user_roles', 'user_id', 'role_id');
    }
}