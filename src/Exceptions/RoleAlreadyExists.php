<?php


namespace Denismitr\Permissions\Exceptions;


class RoleAlreadyExists extends \Exception
{
    public static function create(string $name): self
    {
        return new static("A `{$name}` role already exists.");
    }
}