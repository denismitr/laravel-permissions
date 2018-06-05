<?php


namespace Denismitr\Permissions\Exception;


class RoleAlreadyExists extends \Exception
{
    public static function create(string $name, string $guard, int $teamId = null): self
    {
        if ( ! $teamId) {
            return new static("A `{$name}` role already exists for guard `{$guard}`.");
        }

        return new static("A `{$name}` role already exists for guard `{$guard}` and team ID `$teamId`.");
    }
}