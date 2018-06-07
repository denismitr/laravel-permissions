<?php


namespace Denismitr\Permissions\Contracts;


interface HasGuard
{
    public function getGuard(): string;
}