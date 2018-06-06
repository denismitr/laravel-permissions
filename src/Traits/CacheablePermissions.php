<?php


namespace Denismitr\LTP\Traits;


use Denismitr\LTP\PermissionLoader;

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