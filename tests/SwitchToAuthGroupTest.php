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

class SwitchToAuthGroupTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Permission::create(['name' => 'other-permission']);
    }

    /**
     * @test
     */
    public function it_can_be_created_without_owner()
    {
        // Given
        $user = User::create(['email' => 'new@user.com']);
        $authGroupA = AuthGroup::create(['name' => 'Auth group A']);
        $authGroupB = AuthGroup::create(['name' => 'Auth group B']);

        // Do that
        $user->joinAuthGroup($authGroupA);
        $user->joinAuthGroup($authGroupB);

        // Expect user is on two authGroups
        $this->assertTrue($user->isOneOf($authGroupA));
        $this->assertTrue($user->isOneOf($authGroupB));

        // Do switch to authGroupB
        $user->switchToAuthGroup($authGroupB);

        // Assert currentAuthGroup method works correctly
        $this->assertInstanceOf(AuthGroup::class, $user->currentAuthGroup());
        $this->assertTrue($user->currentAuthGroup()->is($authGroupB));

        // Expect current_auth_group_id to be set to $authGroupB->id
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_auth_group_id' => $authGroupB->id
        ]);
    }

    /** @test */
    public function current_auth_group_can_be_nullable()
    {
        // Given
        $user = User::create(['email' => 'new@user.com']);

        $this->assertNull($user->currentAuthGroup());
        $this->assertNull($user->currentAuthGroupName());
    }

    /** @test */
    public function current_auth_group_will_default_to_the_first_auth_group()
    {
        // Given
        $user = User::create(['email' => 'new@user.com']);

        $authGroupA = AuthGroup::create(['name' => 'Auth group A']);
        $authGroupB = AuthGroup::create(['name' => 'Auth group B']);

        // Do that
        $user->joinAuthGroup($authGroupA);
        $user->joinAuthGroup($authGroupB);

        // Expect currentAuthGroup to default to $authGroupA
        $this->assertInstanceOf(AuthGroup::class, $user->currentAuthGroup());
        $this->assertTrue($user->currentAuthGroup()->is($authGroupA));

        // Expect current_auth_group_id to be set to $authGroupA->id
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_auth_group_id' => $authGroupA->id
        ]);
    }

     /** @test */
     public function it_can_get_current_auth_group_name_from_user()
     {
        // Given
        $user = User::create(['email' => 'new@user.com']);
 
        $authGroupA = AuthGroup::create(['name' => 'Auth group A']);
        $authGroupB = AuthGroup::create(['name' => 'Auth group B']);
 
        // Do that
        $user->joinAuthGroup($authGroupA);
        $user->joinAuthGroup($authGroupB);

        // Do switch to authGroupB
        $user->switchToAuthGroup($authGroupB);
 
        // Expect currentAuthGroupName to be that of $authGroupB
        $this->assertEquals('Auth group B', $user->currentAuthGroupName());
     }

}