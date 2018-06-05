<?php

namespace Denismitr\Permissions\Models;

use Denismitr\Permissions\Exception\PermissionAlreadyExists;
use Denismitr\Permissions\Guard;
use Denismitr\Permissions\PermissionLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Permission extends Model
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


    public function roles()
    {
        return $this
            ->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('team_id');
    }


    public static function fromName(string $name)
    {
        return self::updateOrCreate([
            'name' => $name
        ]);
    }


    public static function byName(string $name)
    {
        return self::where('name', $name)->first();
    }

    /**
     * @return Collection
     */
    public static function getPermissions(): Collection
    {
        return app(PermissionLoader::class)->getPermissions();
    }
}
