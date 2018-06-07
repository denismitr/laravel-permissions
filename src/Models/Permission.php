<?php

namespace Denismitr\Permissions\Models;

use Denismitr\Permissions\Contracts\UserPermission;
use Denismitr\Permissions\Exceptions\PermissionAlreadyExists;
use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Guard;
use Denismitr\Permissions\PermissionLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

class Permission extends Model implements UserPermission
{
    protected $guarded = ['id'];


    /**
     * @param array $attributes
     * @return $this|Model
     */
    public static function create(array $attributes = []): self
    {
        static::getPermissions()->each(function ($permission) use ($attributes) {
            if ($permission->name === $attributes['name']) {
                throw PermissionAlreadyExists::create($attributes['name']);
            }
        })->first();

        app(PermissionLoader::class)->forgetCachedPermissions();

        return static::query()->create($attributes);
    }

    /**
     * @param int $id
     * @return UserPermission
     * @throws PermissionDoesNotExist
     */
    public static function findById(int $id): UserPermission
    {
        $permission = static::getPermissions()->where('id', $id)->first();

        if ( ! $permission) {
            throw PermissionDoesNotExist::createWithId($id);
        }

        return $permission;
    }


    /**
     * @param string $name
     * @return UserPermission
     * @throws PermissionDoesNotExist
     */
    public static function findByName(string $name): UserPermission
    {
        $permission = static::find($name);

        if ( ! $permission) {
            throw PermissionDoesNotExist::create($name);
        }

        return $permission;
    }

    /**
     * @param string $name
     * @return UserPermission
     */
    public static function findOrCreate(string $name): UserPermission
    {
        $permission = static::find($name);

        if ( ! $permission) {
            return static::create(['name' => $name]);
        }

        return $permission;
    }
    
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this
            ->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * @return MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('permissions.models.user'),
            'user',
            'user_permissions',
                'permission_id',
            'user_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * @return Collection
     */
    public static function getPermissions(): Collection
    {
        return app(PermissionLoader::class)->getPermissions();
    }

    /**
     * @param string $name
     * @return Permission|null
     */
    protected static function find(string $name): ?Permission
    {
        $permission = static::getPermissions()->filter(function ($permission) use ($name) {
            return $permission->name === $name;
        })->first();

        return $permission;
    }
}
