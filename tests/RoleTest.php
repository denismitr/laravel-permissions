<?php


namespace Denismitr\LTP\Test;


use Denismitr\LTP\Exceptions\GuardMismatch;
use Denismitr\LTP\Exceptions\PermissionDoesNotExist;
use Denismitr\LTP\Exceptions\RoleAlreadyExists;
use Denismitr\LTP\Models\Permission;
use Denismitr\LTP\Models\Role;
use Denismitr\LTP\Test\Models\Admin;
use Denismitr\LTP\Test\Models\User;

class RoleTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Permission::create(['name' => 'other-permission']);
        Permission::create(['name' => 'wrong-guard-permission', 'guard' => 'admin']);
    }

    /** @test */
    public function it_has_user_models_of_the_right_class()
    {
        $this->admin->assignRole($this->roleAdmin);
        $this->user->assignRole($this->roleUser);

        $this->assertCount(1, $this->roleAdmin->users);
        $this->assertCount(1, $this->roleUser->users);

        $this->assertTrue($this->roleUser->users->first()->is($this->user));
        $this->assertTrue($this->roleAdmin->users->first()->is($this->admin));
        $this->assertInstanceOf(User::class, $this->roleUser->users->first());
        $this->assertInstanceOf(Admin::class, $this->roleAdmin->users->first());
    }

    /** @test */
    public function it_throws_an_exception_when_the_role_already_exists()
    {
        $this->expectException(RoleAlreadyExists::class);

        app(Role::class)->create(['name' => 'test-role']);
        app(Role::class)->create(['name' => 'test-role']);
    }
    
    /** @test */
    public function it_can_be_given_a_permission()
    {
        $this->roleUser->givePermissionTo('edit-articles');

        $this->assertTrue($this->roleUser->hasPermissionTo('edit-articles'));
    }
    
    /** @test */
    public function it_throws_an_exception_when_given_a_permission_that_belongs_to_another_guard()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->roleUser->givePermissionTo('admin-actions');

        $this->expectException(GuardMismatch::class);

        $this->roleUser->givePermissionTo($this->adminPermission);
    }

    /** @test */
    public function role_can_receive_multiple_permissions_as_array()
    {
        $this->roleUser->givePermissionTo(['edit-articles', 'edit-news']);

        $this->assertTrue($this->roleUser->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->roleUser->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function role_can_receive_multiple_permissions_as_arguments()
    {
        $this->roleUser->givePermissionTo('edit-articles', 'edit-news');

        $this->assertTrue($this->roleUser->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->roleUser->hasPermissionTo('edit-news'));
    }
    
    /** @test */
    public function it_can_sync_permissions()
    {
        $this->roleUser->givePermissionTo('edit-articles');
        $this->roleUser->syncPermissions('edit-news');

        $this->assertFalse($this->roleUser->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->roleUser->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_do_not_exist()
    {
        $this->roleUser->givePermissionTo('edit-articles');

        $this->expectException(PermissionDoesNotExist::class);

        $this->roleUser->syncPermissions('permission-does-not-exist');
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_belong_to_a_different_guard()
    {
        $this->roleUser->givePermissionTo('edit-articles');

        $this->expectException(PermissionDoesNotExist::class);

        $this->roleUser->syncPermissions('admin-actions');

        $this->expectException(GuardMismatch::class);

        $this->roleUser->syncPermissions($this->adminPermission);
    }

    /** @test */
    public function it_will_remove_all_permissions_when_passing_an_empty_array_to_sync_permissions()
    {
        $this->roleUser->givePermissionTo('edit-articles');
        $this->roleUser->givePermissionTo('edit-news');
        $this->roleUser->syncPermissions([]);

        $this->assertFalse($this->roleUser->hasPermissionTo('edit-articles'));
        $this->assertFalse($this->roleUser->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_revoke_a_permission()
    {
        $this->roleUser->givePermissionTo('edit-articles');
        $this->assertTrue($this->roleUser->hasPermissionTo('edit-articles'));

        $this->roleUser->revokePermissionTo('edit-articles');
        $this->roleUser = $this->roleUser->fresh();

        $this->assertFalse($this->roleUser->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_can_be_assigned_a_permission_using_objects()
    {
        $this->roleUser->givePermissionTo($this->editArticlesPermission);

        $this->assertTrue($this->roleUser->hasPermissionTo($this->editArticlesPermission));
    }

    /** @test */
    public function it_returns_false_if_role_does_not_have_the_permission()
    {
        $this->assertFalse($this->roleAdmin->hasPermissionTo('wrong-permission'));
    }

    /** @test */
    public function it_does_not_throw_an_exception_if_the_permission_does_not_exist()
    {
        $this->assertFalse($this->roleUser->hasPermissionTo('doesnt-exist'));
    }

    /** @test */
    public function it_returns_false_if_it_does_not_have_a_permission_object()
    {
        $permission = app(Permission::class)->findByName('other-permission');

        $this->assertFalse($this->roleUser->hasPermissionTo($permission));
    }

    /** @test */
    public function it_returns_false_when_a_permission_of_the_wrong_guard_is_passed_in()
    {
        $permission = app(Permission::class)->findByName('wrong-guard-permission', 'admin');

        $this->assertFalse($this->roleUser->hasPermissionTo($permission));
    }
}