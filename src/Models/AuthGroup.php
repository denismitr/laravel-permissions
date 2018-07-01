<?php

namespace Denismitr\Permissions\Models;


use Denismitr\Permissions\Contracts\UserRole;
use Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists;
use Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist;
use Denismitr\Permissions\Exceptions\AuthGroupUserNotFound;
use Denismitr\Permissions\Traits\AuthGroupPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AuthGroup extends Model implements UserRole
{
    use AuthGroupPermissions;

    protected $guarded = ['id'];

    protected $casts = [
        'owner_id' => 'integer'
    ];

    /**
     * @param array $attributes
     * @return $this|Model
     * @throws AuthGroupAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        if (static::whereName($attributes['name'])->first()) {
            throw AuthGroupAlreadyExists::create($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * @param string $name
     * @return AuthGroup
     * @throws AuthGroupDoesNotExist
     */
    public static function named(string $name): self
    {
        return static::findByName($name);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('permissions.models.user'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class,
            config('permissions.tables.auth_group_permissions')
        );
    }


    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permissions.models.user'),
            'auth_group_users'
        );
    }

    /**
     * @return HasMany
     */
    public function authGroupUsers(): HasMany
    {
        return $this->hasMany(AuthGroupUser::class, 'auth_group_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * @return bool
     */
    public function hasOwner(): bool
    {
        return !! $this->owner_id;
    }

    /**
     * @return bool
     */
    public function isTeam(): bool
    {
        return $this->hasOwner();
    }

    /**
     * @param string $name
     * @return AuthGroup
     * @throws AuthGroupDoesNotExist
     */
    public static function findByName(string $name): self
    {
        $role = static::query()->whereName($name)->first();

        if ( ! $role ) {
            throw AuthGroupDoesNotExist::create($name);
        }

        return $role;
    }

    /**
     * @param int $id
     * @return AuthGroup
     * @throws AuthGroupDoesNotExist
     */
    public static function findById(int $id): self
    {
        /** @var AuthGroup $role */
        $role = static::query()->find($id);

        if ( ! $role ) {
            throw AuthGroupDoesNotExist::createWithId($id);
        }

        return $role;
    }

    /**
     * @param $authGroup
     * @return AuthGroup
     * @throws AuthGroupDoesNotExist
     */
    public static function find($authGroup)
    {
        if ( is_numeric($authGroup) ) {
            return static::findById($authGroup);
        }

        if (is_string($authGroup)) {
            return static::findByName($authGroup);
        }

        if ($authGroup instanceof AuthGroup) {
            return $authGroup;
        }

        throw new AuthGroupDoesNotExist();
    }

    /**
     * @param string $name
     * @param null $guard
     * @return UserRole
     * @throws AuthGroupAlreadyExists
     */
    public static function findOrCreate(string $name, $guard = null): UserRole
    {
        $role = static::query()->whereName('name', $name)->first();

        if ( ! $role) {
            return static::create(['name' => $name]);
        }

        return $role;
    }

    /**
     *  Verify if role has a permission
     *
     * @param  string $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_object($permission)) {
            return $this->permissions->contains('id', $permission->id);
        }

        return !! $this->permissions->where('name', $permission)->count();
    }

    /**
     * @param $user
     * @return bool
     */
    public function hasUser($user): bool
    {
        return $this->users->contains($user);
    }

    public function isOwnedBy($user): bool
    {
        return $this->owner_id === (int) $user->id;
    }

    /**
     * @param $user
     * @return AuthGroupUser
     * @throws AuthGroupUserNotFound
     */
    public function forUser($user): AuthGroupUser
    {
        $authGroupUser = $this->authGroupUsers()->where('user_id', $user->id)->first();

        if ( ! $authGroupUser) {
            throw new AuthGroupUserNotFound("Auth group user with email `{$user->email}` not found!");
        }

        return $authGroupUser;
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    /**
     * @param $user
     * @param string $role
     * @return AuthGroup
     */
    public function addUser($user, string $role = 'User'): self
    {
        $model = config('permissions.models.user');

        if ( ! $user instanceof $model) {
            throw new \InvalidArgumentException(
                'User must be an instance of ' . config('permissions.models.user') . '.'
            );
        }

        $this->users()->save($user, ['role' => $role]);

        return $this;
    }
}
