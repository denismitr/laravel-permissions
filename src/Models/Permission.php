<?php

namespace Denismitr\Permissions\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'roles_permissions');
    }


    public static function fromName($name)
    {
        return self::updateOrCreate([
            'name' => $name
        ]);
    }
}
