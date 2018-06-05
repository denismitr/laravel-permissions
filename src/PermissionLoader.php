<?php


namespace Denismitr\Permissions;


use Denismitr\Permissions\Models\Permission;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;

class PermissionLoader
{
    /** @var Gate */
    protected $gate;

    /** @var Repository */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheKey = 'denismitr.permissions.cache';

    /**
     * PermissionLoader constructor.
     * @param Gate $gate
     * @param Repository $cache
     */
    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate = $gate;
        $this->cache = $cache;
    }

    /**
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->cache->remember($this->cacheKey, config('permissions.cache_expiration_time'), function () {
            return app(Permission::class)->with('role')->get();
        });
    }
}