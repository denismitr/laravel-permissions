<?php


namespace Denismitr\Permissions\Exception;


class PermissionAlreadyExists extends \Exception
{
    public static function create(string $name, string $guard, int $teamId = null): self
    {
        if ( ! $teamId) {
            return new static("A `{$name}` permission already exists for guard `{$guard}`.");
        }

        return new static("A `{$name}` permission already exists for guard `{$guard}` and team ID `$teamId`.");
    }
}