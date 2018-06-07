<?php

namespace Denismitr\Permissions\Models;


use Denismitr\Permissions\Contracts\UserRole;
use Denismitr\Permissions\Exceptions\RoleAlreadyExists;
use Denismitr\Permissions\Exceptions\RoleDoesNotExist;
use Denismitr\Permissions\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model implements UserRole
{
    use HasPermissions;

    protected $guarded = ['id'];

    /**
     * @param array $attributes
     * @return $this|Model
     * @throws RoleAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        if (static::whereName($attributes['name'])->first()) {
            throw RoleAlreadyExists::create($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * @param string $name
     * @return Role
     * @throws RoleDoesNotExist
     */
    public static function findByName(string $name): self
    {
        $role = static::query()->whereName($name)->first();

        if ( ! $role ) {
            throw RoleDoesNotExist::create($name);
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
     * @param string $name
     * @param null $guard
     * @return UserRole
     * @throws RoleAlreadyExists
     */
    public static function findOrCreate(string $name, $guard = null): UserRole
    {
        $role = static::query()->whereName('name', $name)->first();

        if ( ! $role) {
            return static::create(['name' => $name]);
        }

        return $role;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permissions.models.permission'),
            config('permissions.table_names.role_permissions')
        );
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('permissions.models.user'),
            'user',
            'user_roles',
            'role_id',
            'user_id'
        );
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
}
