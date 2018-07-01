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

    /**
     * @var User
     */
    protected $wrongUser;

    public function setUp()
    {
        parent::setUp();

        $this->owner = User::create(['email' => 'owner@acme.com']);

        $this->userA = User::create(['email' => 'user.a@acme.com']);
        $this->userB = User::create(['email' => 'user.b@acme.com']);
        $this->wrongUser = User::create(['email' => 'wrong@user.com']);
    }

    /**
     * @test
     * @throws \Denismitr\Permissions\Exceptions\PermissionDoesNotExist
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists
     * @throws \Denismitr\Permissions\Exceptions\UserCannotOwnAuthGroups
     */
    public function user_can_create_a_private_auth_group()
    {
        // Given we have a private auth group
        $authGroup = $this->owner->createNewAuthGroup('Acme','My company auth group');

        // Do invite users to the group
        $authGroup
            ->addUser($this->userA, 'specialist')
            ->addUser($this->userB, 'useless worker');

        // Expect users to belong to the group
        $this->assertTrue($this->userA->isOneOf('Acme'));
        $this->assertTrue($this->userB->isOneOf('Acme'));
        $this->assertTrue($this->owner->isOneOf('Acme'));

        // Expect authGroup to know which users it contains
        $this->assertTrue($authGroup->hasUser($this->userA));
        $this->assertTrue($authGroup->hasUser($this->userA));
        $this->assertTrue($authGroup->hasUser($this->owner));
        $this->assertFalse($authGroup->hasUser($this->wrongUser));

        // Expect authGroup to know it's owner
        $this->assertTrue($authGroup->isOwnedBy($this->owner));
        $this->assertFalse($authGroup->isOwnedBy($this->userB));
        $this->assertFalse($authGroup->isOwnedBy($this->wrongUser));
    }

    /** @test */
    public function owner_can_give_users_permissions_through_the_team()
    {
        // Given we have a private auth group
        $authGroup = $this->owner->createNewAuthGroup('Acme','My company auth group');

        // Do invite users to the group
        $authGroup
            ->addUser($this->userA, 'specialist')
            ->addUser($this->userB, 'useless worker');

        // Do give auth group a permission
        $authGroup->givePermissionTo($this->editArticlesPermission);
        $authGroup->givePermissionTo($this->editBlogPermission);

        // Expect userA to have corresponding permissions
        $this->assertTrue($this->userA->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->userA->isAllowedTo('edit-blog'));

        // Expect userB to have corresponding permissions
        $this->assertTrue($this->userB->hasPermissionTo('edit-blog'));
        $this->assertTrue($this->userB->isAllowedTo('edit-articles'));

        // Do revoke permissions from the group
        $authGroup->revokePermissionTo($this->editArticlesPermission);
        $authGroup->revokePermissionTo($this->editBlogPermission);

        // Expect userA to loose all permissions
        $this->assertFalse($this->userA->hasPermissionTo('edit-articles'));
        $this->assertFalse($this->userA->isAllowedTo('edit-blog'));

        // Expect userB to loose all permissions
        $this->assertFalse($this->userB->hasPermissionTo('edit-blog'));
        $this->assertFalse($this->userB->isAllowedTo('edit-articles'));
    }
}