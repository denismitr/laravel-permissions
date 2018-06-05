<?php

if ( ! function_exists('get_model_for_guard') ) {
    function get_model_for_guard(string $guard): string
    {
        return collect(config('auth.guard'))
            ->map(function ($guard) {
                return config("auth.providers.{$guard['provider']}.model");
            })->get($guard);
    }
}

if ( ! function_exists('is_lumen') ) {
    function is_lumen(): bool
    {
        return preg_match('/lumen/i', app()->version());
    }
}

