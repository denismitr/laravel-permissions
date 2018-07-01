<?php

namespace Denismitr\Permissions\Models;

use Denismitr\Permissions\Exceptions\PermissionAlreadyExists;
use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Loader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Permission extends Model
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

        app(Loader::class)->forgetCachedPermissions();

        return static::query()->create($attributes);
    }

    /**
     * @param int $id
     * @return Permission
     * @throws PermissionDoesNotExist
     */
    public static function findById(int $id): self
    {
        $permission = static::getPermissions()->where('id', $id)->first();

        if ( ! $permission) {
            throw PermissionDoesNotExist::createWithId($id);
        }

        return $permission;
    }


    /**
     * @param string $name
     * @return Permission
     * @throws PermissionDoesNotExist
     */
    public static function findByName(string $name): self
    {
        $permission = static::find($name);

        if ( ! $permission) {
            throw PermissionDoesNotExist::create($name);
        }

        return $permission;
    }

    /**
     * @param string $name
     * @return Permission
     */
    public static function findOrCreate(string $name): self
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
    public function groups(): BelongsToMany
    {
        return $this
            ->belongsToMany(AuthGroup::class, 'auth_group_permissions');
    }

    /**
     * @return BelongsToMany
     */
    public function authGroupUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            AuthGroupUser::class,
            'auth_group_user_permissions',
            'permission_id',
            'auth_group_user_id'
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
        return app(Loader::class)->getPermissions();
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

    public function isGrantedFor($user)
    {
        $this->load('authGroupUsers');

        return $this->authGroupUsers->contains('user_id', $user->id);
    }
}
