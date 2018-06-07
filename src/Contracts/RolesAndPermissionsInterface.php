<?php

namespace Denismitr\Permissions\Contracts;

interface RolesAndPermissionsInterface
{
    /**
     * Check if the user has a given permission
     *
     * @param  Denismitr\Permissions\Models\Permission|string  $permission
     * @return bool
     */
    public function hasPermissionTo(...$permissions);

    /**
     * Give the user a certain permission|s
     * @param  string $permissions
     * @return $this
     */
    public function givePermissionTo(...$permissions);

    /**
     * Give all permissions to user
     *
     * @return $this
     */
    public function grantAllPermissions();

    /**
     * Update permission for a user
     *
     * @param  [string] $permissions
     * @return $this
     */
    public function updatePermissions(...$permissions);
}