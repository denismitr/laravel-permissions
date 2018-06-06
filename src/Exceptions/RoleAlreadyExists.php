<?php


namespace Denismitr\LTP\Exceptions;


class RoleAlreadyExists extends \Exception
{
    public static function create(string $name, string $guard): self
    {
        return new static("A `{$name}` role already exists for guard `{$guard}`");
    }
}