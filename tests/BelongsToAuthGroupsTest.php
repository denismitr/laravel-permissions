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

    /** @test */
    public function user_can_belong_to_multiple_auth_groups()
    {
        // Given we have several auth groups and a user
        $bloggers = AuthGroup::create(['name' => 'bloggers']);
        $editors = AuthGroup::create(['name' => 'editors']);

        /** @var User $user */
        $user = User::create(['email' => 'user.a@test.com']);
        $privateGroup = $user->createNewAuthGroup('private group');

        $user->joinAuthGroup($bloggers, 'Invited user');
        $user->joinAuthGroup($editors, 'Supervisor');

        $userGroups = $user->fresh()->authGroups;

        $this->assertCount(3, $userGroups);

        $userGroups->assertContains($bloggers);
        $userGroups->assertContains($editors);
        $userGroups->assertContains($privateGroup);

        $this->assertEquals('Invited user', $user->onAuthGroup($bloggers)->getRole());
        $this->assertEquals('Supervisor', $user->onAuthGroup($editors)->getRole());
        $this->assertEquals('Owner', $user->onAuthGroup($privateGroup)->getRole());

        $this->assertTrue($user->onAuthGroup($bloggers)->hasRole('Invited user'));
        $this->assertTrue($user->onAuthGroup($editors)->hasRole('Supervisor'));
        $this->assertTrue($user->onAuthGroup($privateGroup)->hasRole('Owner'));
        $this->assertFalse($user->onAuthGroup($privateGroup)->hasRole('Pinguin'));
    }
}