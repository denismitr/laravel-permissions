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
}