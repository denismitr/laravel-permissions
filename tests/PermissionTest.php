<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\PermissionAlreadyExists;
use Denismitr\Permissions\Models\Permission;
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


    /**
     * @test
     * @throws \Denismitr\Permissions\Exceptions\AuthGroupUserNotFound
     */
    public function permission_can_be_given_to_auth_group_user()
    {
        $this->user->joinAuthGroup($this->usersGroup);

        $this->user->onAuthGroup($this->usersGroup)->grantPermissionTo('edit-blog');

        $this->user->onAuthGroup('users')->allowTo($this->editArticlesPermission);

        $this->assertTrue($this->editArticlesPermission->isGrantedFor($this->user));

        $this->admin->joinAuthGroup('admins');

        $this->admin->onAuthGroup('admins')->grantPermissionTo('administrate-blog');

        $this->assertTrue($this->blogAdminPermission->isGrantedFor($this->admin));
    }
}