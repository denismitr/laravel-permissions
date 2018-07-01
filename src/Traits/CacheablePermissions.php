<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Loader;

trait CacheablePermissions
{
    public static function bootCacheablePermissions()
    {
        static::saved(function () {
            app(Loader::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app(Loader::class)->forgetCachedPermissions();
        });
    }
}