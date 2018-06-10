<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\GuardMismatch;
use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Test\Models\User;

class HasPermissionsTest extends TestCase
{
    /** @test */
    public function it_can_assign_a_permission_to_a_user()
    {
        $this->user->givePermissionTo($this->editArticlesPermission);

        $this->refreshUser();

        $this->assertTrue($this->user->hasPermissionTo($this->editArticlesPermission));
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_permission_that_does_not_exist()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->user->givePermissionTo('permission-does-not-exist');
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_permission_to_a_user_from_a_different_guard()
    {
        $this->expectException(GuardMismatch::class);

        $this->user->givePermissionTo($this->adminPermission);

        $this->expectException(PermissionDoesNotExist::class);

        $this->user->givePermissionTo('admin-permission');
    }

    /** @test */
    public function it_can_revoke_a_permission_from_a_user()
    {
        $this->user->givePermissionTo($this->editArticlesPermission);

        $this->refreshUser();

        $this->assertTrue($this->user->hasPermissionTo($this->editArticlesPermission));

        $this->user->revokePermissionTo($this->editArticlesPermission);

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

        $userA->givePermissionTo(['edit-articles', 'edit-news']);

        $this->usersGroup->givePermissionTo('edit-articles');

        $userB->joinAuthGroup($this->usersGroup);

        $scopedUsers1 = User::withPermissions('edit-articles')->get();
        $scopedUsers2 = User::withPermissions(['edit-news'])->get();

        $this->assertEquals(2, $scopedUsers1->count());
//        $this->assertEquals($scopedUsers2->count(), 1);
    }

}