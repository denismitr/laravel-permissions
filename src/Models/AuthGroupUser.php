<?php


namespace Denismitr\Permissions\Models;


use Denismitr\Permissions\Exceptions\AuthGroupUserNotFound;
use Denismitr\Permissions\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthGroupUser extends Model
{
    use HasPermissions;

    protected $table = 'auth_group_users';

    protected $guarded = ['id'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function authGroup(): BelongsTo
    {
        return $this->belongsTo(AuthGroup::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('permissions.models.user'));
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Permission::class,
                'auth_group_user_permissions',
                'auth_group_user_id'
            );
    }

    /**
     * @param int $authGroupId
     * @param int $userId
     * @return AuthGroupUser
     * @throws AuthGroupUserNotFound
     */
    public static function findByAuthGroupAndUser(int $authGroupId, int $userId): self
    {
        $found = static::where('auth_group_id', $authGroupId)->where('user_id', $userId)->first();

        if ( ! $found) {
            throw new AuthGroupUserNotFound();
        }

        return $found;
    }
}