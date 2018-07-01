<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\GuardMismatch;
use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Test\Models\User;

class HasPermissionsTest extends TestCase
{
    /** @test */
    public function it_can_assign_a_permission_to_a_user_via_auth_group_and_check_it()
    {
        $this->usersGroup->addUser($this->user);

        $this->user->grantPermissionsOnAuthGroup('users', $this->editArticlesPermission);

        $this->refreshUser();

        $this->assertTrue($this->user->hasPermissionTo($this->editArticlesPermission));
        $this->assertFalse($this->user->hasPermissionTo($this->adminPermission));
        $this->assertFalse($this->user->hasPermissionTo($this->editBlogPermission));
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_permission_that_does_not_exist()
    {
        $this->usersGroup->addUser($this->user);

        $this->expectException(PermissionDoesNotExist::class);

        $this->user->grantPermissionsOnAuthGroup($this->usersGroup, 'permission-does-not-exist');
    }


    /** @test */
    public function it_can_revoke_a_permission_from_a_user()
    {
        $this->usersGroup->addUser($this->user);

        $this->user->grantPermissionsOnAuthGroup($this->usersGroup, $this->editArticlesPermission);

        $this->refreshUser();

        $this->assertTrue($this->user->hasPermissionTo($this->editArticlesPermission));

        $this->user->revokePermissionOnAuthGroup($this->usersGroup, $this->editArticlesPermission);

        $this->refreshUser();

        $this->assertFalse($this->user->hasPermissionTo($this->editArticlesPermission));
    }

    /** @test */
    public function it_can_scope_users_using_a_string()
    {
        /** @var User $user1 */
        $userA = User::create(['email' => 'user1@test.com']);

        /** @var User $user1 */
        $userB = User::create(['email' => 'user2@test.com']);

        // Given that user is a part of users group
        $this->usersGroup->addUser($userA);

        $userA->grantPermissionsOnAuthGroup($this->usersGroup, ['edit-articles', 'edit-news']);

        $this->usersGroup->givePermissionTo('edit-articles');

        $userB->joinAuthGroup($this->usersGroup);

        $scopedUsers1 = User::withPermissions('edit-articles')->get();
        $scopedUsers2 = User::withPermissions(['edit-news'])->get();

        $this->assertEquals(2, $scopedUsers1->count());
        $this->assertEquals($scopedUsers2->count(), 1);

        $scopedUsers1 = User::allowed('edit-articles')->get();
        $scopedUsers2 = User::allowed(['edit-news'])->get();

        $this->assertEquals(2, $scopedUsers1->count());
        $this->assertEquals($scopedUsers2->count(), 1);
    }

}