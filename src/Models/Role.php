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
    public static function fromName($name)
    {
        return self::updateOrCreate([
            'name' => $name
        ]);
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


    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
