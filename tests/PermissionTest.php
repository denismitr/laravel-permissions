<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\PermissionAlreadyExists;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Test\Models\Admin;
use Denismitr\Permissions\Test\Models\User;

class PermissionTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_when_the_permission_already_exists()
    {
        $this->expectException(PermissionAlreadyExists::class);

        app(Permission::class)->create(['name' => 'test-permission']);
        app(Permission::class)->create(['name' => 'test-permission']);
    }

    /** @test */
    public function it_has_a_guard()
    {
        $permission = app(Permission::class)->create(['name' => 'can-edit', 'guard' => 'admin']);

        $this->assertEquals('admin', $permission->getGuard());
    }
    
    /** @test */
    public function it_has_a_default_guard_by_default()
    {
        $this->assertEquals(
            $this->app['config']->get('auth.defaults.guard'),
            $this->editArticlesPermission->guard
        );
    }
    
    /** @test */
    public function it_has_user_model()
    {
        $this->admin->givePermissionTo($this->adminPermission);
        $this->user->givePermissionTo($this->editArticlesPermission);

        $this->assertCount(1, $this->editArticlesPermission->users);
        $this->assertCount(1, $this->adminPermission->users);

        $this->assertTrue($this->editArticlesPermission->users->first()->is($this->user));
        $this->assertTrue($this->adminPermission->users->first()->is($this->admin));

        $this->assertInstanceOf(User::class, $this->editArticlesPermission->users->first());
        $this->assertInstanceOf(Admin::class, $this->adminPermission->users->first());
    }
}