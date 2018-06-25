<?php


namespace Denismitr\Permissions\Test\Models;


use Denismitr\Permissions\Traits\CacheablePermissions;
use Denismitr\Permissions\Traits\AuthGroupPermissions;
use Denismitr\Permissions\Traits\InteractsWithAuthGroups;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;

class User extends Model implements AuthorizableContract, AuthenticatableContract
{
    use Authorizable, Authenticatable, InteractsWithAuthGroups;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email'];

    public $timestamps = false;

    protected $table = 'users';
}