<?php

namespace Denismitr\Permissions\Models;

use App\User;
use Denismitr\Permissions\Exception\RoleDoesNotExist;
use Denismitr\Permissions\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
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
     * @throws \ReflectionException
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? Guard::getDefault(static::class);
        $attributes['team_id'] = $attributes['team_id'] ?? null;

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
     *  Give role a permission
     *
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
     *  Verify if role has a permission
     *
     * @param  string $permission
     * @return bool
     */
    public function hasPermissionTo(string $permission)
    {
        return (bool) $this->permissions->where('name', $permission)->count();
    }
}
