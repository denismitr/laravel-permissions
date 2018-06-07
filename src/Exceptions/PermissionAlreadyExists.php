<?php


namespace Denismitr\Permissions\Exceptions;


class PermissionAlreadyExists extends \Exception
{
    public static function create(string $name, string $guard): self
    {
        return new static("A `{$name}` permission already exists for guard `{$guard}`.");
    }
}