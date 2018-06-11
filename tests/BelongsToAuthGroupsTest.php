<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Test\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;

class BelongsToAuthGroupsTest extends TestCase
{
    /**
     * @test
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists
     */
    public function user_can_have_permissions_via_auth_group()
    {
        $authGroup = AuthGroup::create(['name' => 'My auth group']);

        $userA = User::create(['email' => 'user.a@test.com']);
        $userB = User::create(['email' => 'user.b@test.com']);

        AuthGroup::named('My auth group')->addUser($userA)->addUser($userB);

        $this->assertTrue($userA->isOneOf('My auth group'));
        $this->assertTrue($userB->isOneOf('My auth group'));

        $authGroup->givePermissionTo($this->editArticlesPermission);
        $authGroup->givePermissionTo($this->editBlogPermission);

        $this->assertTrue($userA->hasPermissionTo('edit-articles'));
        $this->assertTrue($userA->isAllowedTo('edit-blog'));

        $this->assertTrue($userB->hasPermissionTo('edit-blog'));
        $this->assertTrue($userB->isAllowedTo('edit-articles'));

        $authGroup->revokePermissionTo($this->editArticlesPermission);
        $authGroup->revokePermissionTo($this->editBlogPermission);

        $this->assertFalse($userA->hasPermissionTo('edit-articles'));
        $this->assertFalse($userA->isAllowedTo('edit-blog'));

        $this->assertFalse($userB->hasPermissionTo('edit-blog'));
        $this->assertFalse($userB->isAllowedTo('edit-articles'));
    }
}