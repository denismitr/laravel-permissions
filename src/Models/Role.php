<?php

namespace Denismitr\LTP\Models;

use App\User;
use Denismitr\LTP\Contracts\HasGuard;
use Denismitr\LTP\Exceptions\GuardMismatch;
use Denismitr\LTP\Exceptions\PermissionDoesNotExist;
use Denismitr\LTP\Exceptions\RoleAlreadyExists;
use Denismitr\LTP\Exceptions\RoleDoesNotExist;
use Denismitr\LTP\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model implements HasGuard
{
    protected $guarded = [];

    /**
     * Role constructor.
     * @param array $attributes
     * @throws \ReflectionException
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? Guard::getDefault(static::class);

        parent::__construct($attributes);
    }

    /**
     * @param array $attributes
     * @return $this|Model
     * @throws RoleAlreadyExists
     * @throws \ReflectionException
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? Guard::getDefault(static::class);
        $attributes['team_id'] = $attributes['team_id'] ?? null;

        if (static::whereName($attributes['name'])->whereGuard($attributes['guard'])->first()) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard']);
        }

        return static::query()->create($attributes);
    }

    /**
     * @param string $name
     * @param string|null $guard
     * @return Role
     * @throws RoleDoesNotExist
     * @throws \ReflectionException
     */
    public static function findByName(string $name, string $guard = null): self
    {
        $guard = $guard ?: Guard::getDefault($guard);

        $role = static::query()->whereName($name)->whereGuard($guard)->first();

        if ( ! $role ) {
            throw RoleDoesNotExist::create($name, $guard);
        }

        return $role;
    }

    /**
     * @param int $id
     * @return Role
     * @throws RoleDoesNotExist
     */
    public static function findById(int $id): self
    {
        /** @var Role $role */
        $role = static::query()->find($id);

        if ( ! $role ) {
            throw RoleDoesNotExist::createWithId($id);
        }

        return $role;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            Guard::getModelFor($this->attributes['guard']),
            'user',
            'user_roles',
            'role_id',
            'user_id'
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
     * @param array ...$permissions
     * @return Role
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * @param array ...$permissions
     * @return $this
     * @throws PermissionDoesNotExist
     * @throws \ReflectionException
     */
    public function revokePermissionTo(...$permissions)
    {
        $this->permissions()->detach($this->getPermissions($permissions));

        return $this;
    }

    /**
     * @return string
     */
    public function getGuard(): string
    {
        return $this->guard;
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
}
