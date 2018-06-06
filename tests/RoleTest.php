<?php


namespace Denismitr\LTP\Test;


use Denismitr\LTP\Exceptions\GuardMismatch;
use Denismitr\LTP\Exceptions\PermissionDoesNotExist;
use Denismitr\LTP\Exceptions\RoleAlreadyExists;
use Denismitr\LTP\Exceptions\RoleDoesNotExist;
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
        $this->admin->assignRole($this->adminRole);
        $this->user->assignRole($this->userRole);

        $this->assertCount(1, $this->adminRole->users);
        $this->assertCount(1, $this->userRole->users);

        $this->assertTrue($this->userRole->users->first()->is($this->user));
        $this->assertTrue($this->adminRole->users->first()->is($this->admin));
        $this->assertInstanceOf(User::class, $this->userRole->users->first());
        $this->assertInstanceOf(Admin::class, $this->adminRole->users->first());
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
        $this->userRole->givePermissionTo($this->editArticlesPermission->name);

        $this->assertTrue($this->userRole->hasPermissionTo('edit-articles'));
    }
    
    /** @test */
    public function it_throws_an_exception_when_given_a_permission_that_belongs_to_another_guard()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->userRole->givePermissionTo('admin-actions');

        $this->expectException(GuardMismatch::class);

        $this->userRole->givePermissionTo($this->adminPermission);
    }

    /** @test */
    public function role_can_receive_multiple_permissions_as_array()
    {
        $this->userRole->givePermissionTo(['edit-articles', 'edit-news']);

        $this->assertTrue($this->userRole->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->userRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function role_can_receive_multiple_permissions_as_arguments()
    {
        $this->userRole->givePermissionTo('edit-articles', 'edit-news');

        $this->assertTrue($this->userRole->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->userRole->hasPermissionTo('edit-news'));
    }
    
    /** @test */
    public function it_can_sync_permissions()
    {
        $this->userRole->givePermissionTo('edit-articles');
        $this->userRole->syncPermissions('edit-news');

        $this->assertFalse($this->userRole->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->userRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_do_not_exist()
    {
        $this->userRole->givePermissionTo('edit-articles');

        $this->expectException(PermissionDoesNotExist::class);

        $this->userRole->syncPermissions('permission-does-not-exist');
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_belong_to_a_different_guard()
    {
        $this->userRole->givePermissionTo('edit-articles');

        $this->expectException(PermissionDoesNotExist::class);

        $this->userRole->syncPermissions('admin-actions');

        $this->expectException(GuardMismatch::class);

        $this->userRole->syncPermissions($this->adminPermission);
    }

    /** @test */
    public function it_will_remove_all_permissions_when_passing_an_empty_array_to_sync_permissions()
    {
        $this->userRole->givePermissionTo('edit-articles');
        $this->userRole->givePermissionTo('edit-news');
        $this->userRole->syncPermissions([]);

        $this->assertFalse($this->userRole->hasPermissionTo('edit-articles'));
        $this->assertFalse($this->userRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_revoke_a_permission()
    {
        $this->userRole->givePermissionTo('edit-articles');
        $this->assertTrue($this->userRole->hasPermissionTo('edit-articles'));

        $this->userRole->revokePermissionTo('edit-articles');
        $this->userRole = $this->userRole->fresh();

        $this->assertFalse($this->userRole->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_can_be_assigned_a_permission_using_objects()
    {
        $this->userRole->givePermissionTo($this->editArticlesPermission);

        $this->assertTrue($this->userRole->hasPermissionTo($this->editArticlesPermission));
    }

    /** @test */
    public function it_returns_false_if_role_does_not_have_the_permission()
    {
        $this->assertFalse($this->adminRole->hasPermissionTo('wrong-permission'));
    }

    /** @test */
    public function it_does_not_throw_an_exception_if_the_permission_does_not_exist()
    {
        $this->assertFalse($this->userRole->hasPermissionTo('doesnt-exist'));
    }

    /** @test */
    public function it_returns_false_if_it_does_not_have_a_permission_object()
    {
        $this->assertFalse($this->userRole->hasPermissionTo(null));
    }

    /** @test */
    public function it_creates_permission_object_with_findOrCreate_if_it_does_not_have_a_permission_object()
    {
        $permission = app(Permission::class)->findOrCreate('another-permission');

        $this->assertFalse($this->userRole->hasPermissionTo($permission));

        $this->userRole->givePermissionTo($permission);

        $this->refreshUserRole();

        $this->assertTrue($this->userRole->hasPermissionTo('another-permission'));
    }


    /** @test */
    public function it_creates_a_role_with_findOrCreate_if_the_named_role_does_not_exist()
    {
        $this->expectException(RoleDoesNotExist::class);

        $role1 = app(Role::class)->findByName('non-existing-role');

        $this->assertNull($role1);

        $role2 = app(Role::class)->findOrCreate('yet-another-role');

        $this->assertInstanceOf(Role::class, $role2);
    }
}