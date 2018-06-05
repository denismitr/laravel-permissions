<?php


namespace Denismitr\LTP\Exceptions;


use Illuminate\Support\Collection;

class GuardMismatch extends \Exception
{
    public static function create(string $givenGuard, Collection $expectedGuards)
    {
        return new static("The given role or permission should use guard `{$expectedGuards->implode(', ')}` instead of `{$givenGuard}`.");
    }
}