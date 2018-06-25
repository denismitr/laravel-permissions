<?php


namespace Denismitr\Permissions\Exceptions;


class PermissionAlreadyExists extends \Exception
{
    public static function create(string $name): self
    {
        return new static("A `{$name}` permission already exists.");
    }
}