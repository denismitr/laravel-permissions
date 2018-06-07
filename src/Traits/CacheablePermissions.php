<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\PermissionLoader;

trait CacheablePermissions
{
    public static function bootCacheablePermissions()
    {
        static::saved(function () {
            app(PermissionLoader::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app(PermissionLoader::class)->forgetCachedPermissions();
        });
    }
}