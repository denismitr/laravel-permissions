<?php


namespace Denismitr\Permissions\Exceptions;


class AuthGroupDoesNotExist extends \Exception
{
    /**
     * @param string $name
     * @return AuthGroupDoesNotExist
     */
    public static function create(string $name): self
    {
        return new static("A `{$name}` auth group does not exist.");
    }

    /**
     * @param int $id
     * @return AuthGroupDoesNotExist
     */
    public static function createWithId(int $id): self
    {
        return new static("An auth group with `{$id}` does not exist.");
    }
}