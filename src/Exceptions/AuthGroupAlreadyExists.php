<?php


namespace Denismitr\Permissions\Exceptions;


class AuthGroupAlreadyExists extends \Exception
{
    public static function create(string $name): self
    {
        return new static("Auth group with name `{$name}` already exists.");
    }
}