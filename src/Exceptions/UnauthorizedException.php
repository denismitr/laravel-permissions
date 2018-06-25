<?php

namespace Denismitr\Permissions\Exceptions;


use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    const UNAUTHORIZED_STATUS_CODE = 403;
    const GUEST_USER_CODE = 401;

    protected $requiredAuthGroups;

    public static function notMemberOfRequiredAuthGroups($groups): self
    {
        $message = 'User does not belong to required auth group';

        return (new static(self::UNAUTHORIZED_STATUS_CODE, $message, null, []))
            ->setRequiredAuthGroups($groups);
    }

    public static function guestUser(): self
    {
        $message = 'User does not belong to required auth group';

        return new static(self::GUEST_USER_CODE, $message, null, []);
    }

    /**
     * @return mixed
     */
    public function getRequiredAuthGroups()
    {
        return $this->requiredAuthGroups;
    }

    /**
     * @param $groups
     * @return UnauthorizedException
     */
    public function setRequiredAuthGroups($groups): self
    {
        $this->requiredAuthGroups = $groups;

        return $this;
    }
}