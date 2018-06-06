<?php


namespace Denismitr\LTP\Traits;


use Denismitr\LTP\Contracts\HasGuard;
use Denismitr\LTP\Exceptions\GuardMismatch;
use Denismitr\LTP\Exceptions\PermissionDoesNotExist;
use Denismitr\LTP\Guard;
use Denismitr\LTP\Models\Permission;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasPermissions
{
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
                $this->verifySharedGuard($permission);
            })
            ->all();

        $this->permissions()->saveMany($permissions);

        $this->load('permissions');

        return $this;
    }

    /**
     *  Verify if role has a permission
     *
     * @param  string $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_object($permission)) {
            return $this->permissions->contains('id', $permission->id);
        }

        return !! $this->permissions->where('name', $permission)->count();
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
        if (! empty($permissions) && is_string($permissions[0])) {
            return app(Permission::class)
                ->whereIn('name', $permissions)
                ->whereIn('guard', $this->getGuards())
                ->get();
        }

        throw new PermissionDoesNotExist("Permissions list is invalid");
    }

    /**
     * @param HasGuard $roleOrPermission
     * @throws GuardMismatch
     * @throws \ReflectionException
     */
    protected function verifySharedGuard(HasGuard $roleOrPermission)
    {
        if ( ! Guard::getNames($this)->contains($roleOrPermission->getGuard()) ) {
            throw GuardMismatch::create($roleOrPermission->getGuard(), Guard::getNames($this));
        }
    }
}