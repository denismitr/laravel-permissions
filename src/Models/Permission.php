<?php

namespace Denismitr\Permissions\Models;

use Denismitr\Permissions\Contracts\UserPermission;
use Denismitr\Permissions\Exception\PermissionAlreadyExists;
use Denismitr\Permissions\Exception\PermissionDoesNotExist;
use Denismitr\Permissions\Guard;
use Denismitr\Permissions\PermissionLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

class Permission extends Model implements UserPermission
{
    protected $guarded = ['id'];


    public function __construct(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? Guard::getDefault(static::class);
        $attributes['team_id'] = $attributes['team_id'] ?? null;

        static::getPermissions()->each(function ($permission) use ($attributes) {
            if ($permission->name === $attributes['name'] &&
                $permission->guard === $attributes['guard'] &&
                $permission->team_id === $attributes['team_id']) {
                throw PermissionAlreadyExists::create(
                    $attributes['name'],
                    $attributes['guard'],
                    $attributes['team_id']
                );
            }
        })->first();

        return static::query()->create($attributes);
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
            ->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('team_id');
    }

    /**
     * @return MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            Guard::getModelFor($this->attributes['guard']),
            'user',
            'user_permissions',
                'permission_id',
            'user_id'
        );
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
     * @param null $guard
     * @return UserPermission
     * @throws PermissionDoesNotExist
     * @throws \ReflectionException
     */
    public static function findByName(string $name, $guard = null): UserPermission
    {
        $guard = $guard ?? Guard::getDefault(static::class);

        $permission = static::getPermissions()->filter(function ($permission) use ($name, $guard) {
            return $permission->name === $name && $permission->guard === $guard;
        })->first();

        if ( ! $permission) {
            throw PermissionDoesNotExist::create($name, $guard);
        }

        return $permission;
    }

    /**
     * @return Collection
     */
    public static function getPermissions(): Collection
    {
        return app(PermissionLoader::class)->getPermissions();
    }
}
