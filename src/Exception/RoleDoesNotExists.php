<?php


namespace Denismitr\Permissions\Exception;


class RoleDoesNotExist extends \Exception
{
    /**
     * @param string $name
     * @param string $guard
     * @param int|null $teamId
     * @return RoleDoesNotExist
     */
    public static function create(string $name, string $guard, int $teamId = null): self
    {
        if ( ! $teamId) {
            return new static("A `{$name}` role does not exist for guard `{$guard}`.");
        }

        return new static("A `{$name}` role does not exist for guard `{$guard}` and team ID `$teamId`.");
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