<?php


namespace Denismitr\LTP\Test\Models;


use Denismitr\LTP\Traits\CacheablePermissions;
use Denismitr\LTP\Traits\HasPermissions;
use Denismitr\LTP\Traits\HasRoles;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;

class User extends Model implements AuthorizableContract, AuthenticatableContract
{
    use Authorizable, Authenticatable, HasRoles, CacheablePermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email'];

    public $timestamps = false;

    protected $table = 'users';
}