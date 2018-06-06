<?php


namespace Denismitr\LTP\Test;


use Illuminate\Contracts\Auth\Access\Gate;

class GateTest extends TestCase
{
    /** @test */
    public function it_can_determine_if_a_user_does_not_have_a_permission()
    {
        $this->assertFalse($this->user->can('edit_article'));
    }

    /** @test */
    public function it_allows_gate_before_callbacks_to_run_if_a_user_does_not_have_a_permission()
    {
        $this->assertFalse($this->user->can('edit_article'));

        app(Gate::class)->before(function() {
            return true;
        });

        $this->assertTrue($this->user->can('edit_article'));
    }

    /** @test */
    public function it_can_determine_if_a_user_has_a_direct_permission()
    {
        $this->user->givePermissionTo('edit-articles');

        $this->assertTrue($this->user->can('edit-articles'));

        $this->assertFalse($this->user->can('non-existing-permission'));

        $this->assertFalse($this->user->can('admin-permission'));
    }

    /** @test */
    public function it_can_determine_if_a_user_has_a_permission_through_roles()
    {
        $this->userRole->givePermissionTo($this->editArticlesPermission);

        $this->user->assignRole($this->userRole);

        $this->assertTrue($this->user->hasPermissionTo($this->editArticlesPermission));

        $this->assertTrue($this->user->can('edit-articles'));
        $this->assertFalse($this->user->can('non-existing-permission'));
        $this->assertFalse($this->user->can('admin-permission'));
    }

    /** @test */
    public function it_can_determine_if_a_user_with_a_different_guard_has_a_permission_when_using_roles()
    {
        $this->adminRole->givePermissionTo($this->adminPermission);

        $this->admin->assignRole($this->adminRole);

        $this->assertTrue($this->admin->hasPermissionTo($this->adminPermission));

        $this->assertTrue($this->admin->can('admin-permission'));
        $this->assertFalse($this->admin->can('non-existing-permission'));
        $this->assertFalse($this->admin->can('edit-articles'));
    }
}