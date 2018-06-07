<?php


namespace Denismitr\Permissions\Exceptions;


class RoleDoesNotExist extends \Exception
{
    /**
     * @param string $name
     * @return RoleDoesNotExist
     */
    public static function create(string $name): self
    {
        return new static("A `{$name}` role does not exist.");
    }

    /**
     * @param int $id
     * @return RoleDoesNotExist
     */
    public static function createWithId(int $id): self
    {
        return new static("A role with `{$id}` does not exist.");
    }
}