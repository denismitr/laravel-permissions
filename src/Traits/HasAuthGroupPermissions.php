<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Models\AuthGroup;

trait HasAuthGroupPermissions
{
    public function switchToAuthGroup(AuthGroup $authGroup)
    {
        $this->current_auth_group_id = $authGroup->id;

        $this->save();
    }
}