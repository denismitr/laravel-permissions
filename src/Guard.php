<?php


namespace Denismitr\LTP;


use Denismitr\LTP\Contracts\HasGuard;
use Denismitr\LTP\Exceptions\GuardMismatch;
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
     * @param string $guard
     * @return string
     */
    public static function getModelFor(string $guard): string
    {
        return collect(config('auth.guards'))
            ->map(function ($guard) {
                return config("auth.providers.{$guard['provider']}.model");
            })->get($guard);
    }

    /**
     * @param $class
     * @return string
     * @throws \ReflectionException
     */
    public static function getDefault($class): string
    {
        $default = config('auth.defaults.guard');

        return static::getNames($class)->first() ?: $default;
    }

    /**
     * @param HasGuard $roleOrPermission
     * @param $model
     * @throws GuardMismatch
     * @throws \ReflectionException
     */
    public static function verifyIsSharedBetween(HasGuard $roleOrPermission, $model)
    {
        if ( ! static::getNames($model)->contains($roleOrPermission->getGuard()) ) {
            throw GuardMismatch::create($roleOrPermission->getGuard(), static::getNames($model));
        }
    }
}