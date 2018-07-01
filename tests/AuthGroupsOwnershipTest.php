<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Test\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;

class AuthGroupsOwnershipTest extends TestCase
{
    /**
     * @var User
     */
    protected $owner;

    /**
     * @var User
     */
    protected $userA;

    /**
     * @var User
     */
    protected $userB;

    public function setUp()
    {
        parent::setUp();

        $this->owner = User::create(['email' => 'owner@acme.com']);

        $this->userA = User::create(['email' => 'user.a@acme.com']);
        $this->userB = User::create(['email' => 'user.b@acme.com']);
    }

    /**
     * @test
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists
     * @throws \Denismitr\Permissions\Exceptions\UserCannotOwnAuthGroups
     */
    public function user_can_create_a_personal_auth_group()
    {
        $authGroup = $this->owner->createNewAuthGroup('Acme','My company auth group');

        $authGroup
            ->addUser($this->userA)
            ->addUser($this->userB);

        $this->assertTrue($this->userA->isOneOf('Acme'));
        $this->assertTrue($this->userB->isOneOf('Acme'));
        $this->assertTrue($this->owner->isOneOf('Acme'));

        $authGroup->givePermissionTo($this->editArticlesPermission);
        $authGroup->givePermissionTo($this->editBlogPermission);

        $this->assertTrue($this->userA->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->userA->isAllowedTo('edit-blog'));

        $this->assertTrue($this->userB->hasPermissionTo('edit-blog'));
        $this->assertTrue($this->userB->isAllowedTo('edit-articles'));

        $authGroup->revokePermissionTo($this->editArticlesPermission);
        $authGroup->revokePermissionTo($this->editBlogPermission);

        $this->assertFalse($this->userA->hasPermissionTo('edit-articles'));
        $this->assertFalse($this->userA->isAllowedTo('edit-blog'));

        $this->assertFalse($this->userB->hasPermissionTo('edit-blog'));
        $this->assertFalse($this->userB->isAllowedTo('edit-articles'));
    }
}