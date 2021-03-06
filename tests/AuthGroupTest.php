<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\GuardMismatch;
use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists;
use Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Test\Models\Admin;
use Denismitr\Permissions\Test\Models\User;

class AuthGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'other-permission']);
    }

    /**
     * @test
     * @throws AuthGroupAlreadyExists
     */
    public function it_can_be_created_without_owner()
    {
        $authGroup = AuthGroup::create(['name' => 'New auth group']);

        $this->assertInstanceOf(AuthGroup::class, $authGroup);
        $this->assertEquals('New auth group', $authGroup->name);
        $this->assertNull($authGroup->owner);
        $this->assertFalse($authGroup->hasOwner());
        $this->assertFalse($authGroup->isTeam());
    }

    /** @test */
    public function auth_group_name_must_be_unique()
    {
        $authGroup = AuthGroup::create(['name' => 'New auth group']);

        $this->expectException(AuthGroupAlreadyExists::class);

        $authGroup = AuthGroup::create(['name' => 'New auth group']);
    }

    /** @test */
    public function it_can_verify_if_auth_group_already_exists()
    {
        // Given
        AuthGroup::create(['name' => 'AG 1']);
        AuthGroup::create(['name' => 'AG 2']);

        $this->assertTrue(AuthGroup::existsWithName('AG 2'));
        $this->assertFalse(AuthGroup::existsWithName('AG 3'));
    }

    /**
     * @test
     * @throws AuthGroupAlreadyExists
     */
    public function it_can_be_created_with_owner()
    {
        $authGroup = AuthGroup::create([
            'name' => 'New auth group',
            'description' => 'Some description',
            'owner_id' => $this->user->id
        ]);

        $this->assertInstanceOf(AuthGroup::class, $authGroup);
        $this->assertEquals('New auth group', $authGroup->name);
        $this->assertEquals('Some description', $authGroup->description);
        $this->assertInstanceOf(User::class, $authGroup->owner);
        $this->assertTrue($authGroup->owner->is($this->user));
        $this->assertTrue($authGroup->hasOwner());
        $this->assertTrue($authGroup->isTeam());
    }

    /** @test */
    public function it_throws_an_exception_when_the_role_already_exists()
    {
        $this->expectException(AuthGroupAlreadyExists::class);

        app(AuthGroup::class)->create(['name' => 'test-role']);
        app(AuthGroup::class)->create(['name' => 'test-role']);
    }

    /** @test */
    public function it_can_be_given_a_permission()
    {
        $this->usersGroup->givePermissionTo($this->editArticlesPermission->name);

        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-articles'));
    }


    /** @test */
    public function role_can_receive_multiple_permissions_as_array()
    {
        $this->usersGroup->givePermissionTo(['edit-articles', 'edit-news']);

        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function role_can_receive_multiple_permissions_as_arguments()
    {
        $this->usersGroup->givePermissionTo('edit-articles', 'edit-news');

        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_sync_permissions()
    {
        $this->usersGroup->givePermissionTo('edit-articles');
        $this->usersGroup->syncPermissions('edit-news');

        $this->assertFalse($this->usersGroup->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_do_not_exist()
    {
        $this->usersGroup->givePermissionTo('edit-articles');

        $this->expectException(PermissionDoesNotExist::class);

        $this->usersGroup->syncPermissions('permission-does-not-exist');
    }

    /** @test */
    public function it_will_remove_all_permissions_when_passing_an_empty_array_to_sync_permissions()
    {
        $this->usersGroup->givePermissionTo('edit-articles');
        $this->usersGroup->givePermissionTo('edit-news');
        $this->usersGroup->syncPermissions([]);

        $this->assertFalse($this->usersGroup->hasPermissionTo('edit-articles'));
        $this->assertFalse($this->usersGroup->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_revoke_a_permission()
    {
        $this->usersGroup->givePermissionTo('edit-articles');
        $this->assertTrue($this->usersGroup->hasPermissionTo('edit-articles'));

        $this->usersGroup->revokePermissionTo('edit-articles');
        $this->usersGroup = $this->usersGroup->fresh();

        $this->assertFalse($this->usersGroup->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_can_be_assigned_a_permission_using_objects()
    {
        $this->usersGroup->givePermissionTo($this->editArticlesPermission);

        $this->assertTrue($this->usersGroup->hasPermissionTo($this->editArticlesPermission));
    }

    /** @test */
    public function it_returns_false_if_role_does_not_have_the_permission()
    {
        $this->assertFalse($this->adminsGroup->hasPermissionTo('wrong-permission'));
    }

    /** @test */
    public function it_does_not_throw_an_exception_if_the_permission_does_not_exist()
    {
        $this->assertFalse($this->usersGroup->hasPermissionTo('doesnt-exist'));
    }

    /** @test */
    public function it_returns_false_if_it_does_not_have_a_permission_object()
    {
        $this->assertFalse($this->usersGroup->hasPermissionTo(null));
    }

    /** @test */
    public function it_creates_permission_object_with_findOrCreate_if_it_does_not_have_a_permission_object()
    {
        $permission = app(Permission::class)->findOrCreate('another-permission');

        $this->assertFalse($this->usersGroup->hasPermissionTo($permission));

        $this->usersGroup->givePermissionTo($permission);

        $this->assertTrue($this->usersGroup->hasPermissionTo('another-permission'));
    }


    /** @test */
    public function it_creates_a_role_with_findOrCreate_if_the_named_role_does_not_exist()
    {
        $this->expectException(AuthGroupDoesNotExist::class);

        $groupA = app(AuthGroup::class)->findByName('non-existing-group');

        $this->assertNull($groupA);

        $groupB = app(AuthGroup::class)->findOrCreate('yet-another-group');

        $this->assertInstanceOf(AuthGroup::class, $groupB);
    }

    /**
     * @test
     * @throws AuthGroupAlreadyExists
     * @throws AuthGroupDoesNotExist
     */
    public function user_can_be_added_to_an_auth_group()
    {
        $authGroup = AuthGroup::create(['name' => 'My auth group']);

        AuthGroup::named('My auth group')->addUser($this->user);

        $this->assertTrue($this->user->isOneOf('My auth group'));
        $this->assertCount(1, $authGroup->users);
        $this->assertTrue($authGroup->users->first()->is($this->user));
    }

    /** @test */
    public function multiple_users_can_be_added_to_auth_group()
    {
        $authGroup = AuthGroup::create(['name' => 'My auth group']);
        $userA = User::create(['email' => 'user.a@test.com']);
        $userB = User::create(['email' => 'user.b@test.com']);

        AuthGroup::named('My auth group')->addUser($userA)->addUser($userB);

        $this->assertTrue($userA->isOneOf('My auth group'));
        $this->assertTrue($userB->isOneOf('My auth group'));
        $this->assertCount(2, $authGroup->users);
        $this->assertTrue($authGroup->users->contains('id', $userA->id));
        $this->assertTrue($authGroup->users->contains('id', $userB->id));
    }

    /** @test */
    public function user_can_belong_to_several_groups()
    {
        $this->usersGroup->addUser($this->user);
        $this->adminsGroup->addUser($this->user);

        $this->assertTrue($this->user->isOneOf('users'));
        $this->assertTrue($this->user->isOneOf($this->usersGroup));

        $this->assertTrue($this->user->isOneOf('admins'));
        $this->assertTrue($this->user->isOneOf($this->adminsGroup));

        $this->assertTrue($this->user->isOneOfAll('admins', 'users'));
        $this->assertTrue($this->user->isOneOfAll($this->usersGroup, $this->adminsGroup));
    }
}
