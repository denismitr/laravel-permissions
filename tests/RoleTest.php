<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Test\Models\Admin;
use Denismitr\Permissions\Test\Models\User;

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
}