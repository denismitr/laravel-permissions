<?php


namespace Denismitr\Permissions\Exceptions;


class AuthGroupAlreadyExists extends \Exception
{
    public static function create(string $name): self
    {
        return new static("A `{$name}` auth group already exists.");
    }
}