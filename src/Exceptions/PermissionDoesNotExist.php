<?php


namespace Denismitr\Permissions\Exceptions;


class PermissionDoesNotExist extends \Exception
{
    /**
     * @param string $name
     * @param int|null $teamId
     * @return PermissionDoesNotExist
     */
    public static function create(string $name, int $teamId = null): self
    {
        if ( ! $teamId) {
            return new static("A `{$name}` permission does not exist.");
        }

        return new static("A `{$name}` permission does not exist and team ID `$teamId`.");
    }

    /**
     * @param int $id
     * @return PermissionDoesNotExist
     */
    public static function createWithId(int $id): self
    {
        return new static("A permission with `{$id}` does not exist.");
    }
}