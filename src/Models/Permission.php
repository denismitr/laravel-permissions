<?php

namespace Denismitr\Permissions\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = ['id'];


    public function __construct(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);
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
}
