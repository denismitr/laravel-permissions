<?php


namespace Denismitr\Permissions;


use Illuminate\Support\Collection;

class Guard
{
    /**
     * @param $model
     * @return Collection
     * @throws \ReflectionException
     */
    public static function getNames($model): Collection
    {
        if (is_object($model)) {
            $guard = $model->guard ?? null;
        }

        if ( ! isset($guard)) {
            $class = is_object($model) ? get_class($model) : $model;

            $guard = (new \ReflectionClass($class))->getDefaultProperties()['guard'] ?? null;
        }

        if ($guard) {
            return collect($guard);
        }

        return collect(config('auth.guards'))
            ->map(function($guard) {
                if ( ! isset($guard['provider']) ) {
                    return null;
                }

                return config("auth.providers.{$guard['provider']}.model");
            })->filter(function ($model) use ($class) {
                return $class === $model;
            })->keys();
    }

    /**
     * @param $class
     * @return string
     */
    public static function getDefaultName($class): string
    {
        $default = config('auth.defaults.guard');

        return static::getNames($class)->first() ?: $default;
    }
}