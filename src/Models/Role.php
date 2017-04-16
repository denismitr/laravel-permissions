<?php

namespace Denismitr\Permissions\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    /**
     *  Create new instance of Role with new name or old
     *  one if not unique
     *
     * @param  string $name
     * @return Illuminate\Database\Eloquent\Model
     */
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
     * Belongs to many permissions
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions');
    }


    /**
     *  Get users who belong to this role
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
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
