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
    public function it_has_user_model()
    {
        $this->user->givePermissionTo($this->editArticlesPermission);

        $this->assertCount(1, $this->editArticlesPermission->users);

        $this->assertTrue($this->editArticlesPermission->users->first()->is($this->user));

        $this->assertInstanceOf(User::class, $this->editArticlesPermission->users->first());
    }
}