<?php


namespace Denismitr\Permissions;


use Denismitr\Permissions\Models\Permission;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;

class PermissionLoader
{
    /** @var Gate */
    private $gate;

    /** @var Repository */
    private $repository;

    /**
     * PermissionLoader constructor.
     * @param Gate $gate
     * @param Repository $repository
     */
    public function __construct(Gate $gate, Repository $repository)
    {
        $this->gate = $gate;
        $this->repository = $repository;
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