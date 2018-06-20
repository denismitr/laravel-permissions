<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\GuardMismatch;
use Denismitr\Permissions\Exceptions\PermissionDoesNotExist;
use Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists;
use Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist;
use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Test\Models\users;
use Denismitr\Permissions\Test\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;

class BladeDirectivesTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Views for testing
        $finder = new \Illuminate\View\FileViewFinder(app()['files'], array(__DIR__.'/views'));
        View::setFinder($finder);

        AuthGroup::create(['name' => 'authors']);
        AuthGroup::create(['name' => 'bloggers']);
        AuthGroup::create(['name' => 'writers']);

        Permission::create(['name' => 'write-something']);
        Permission::create(['name' => 'blog-something']);
        Permission::create(['name' => 'delete-something']);
        Permission::create(['name' => 'update-something']);
    }

    /** @test */
    public function all_blade_directives_will_evaluate_to_false_for_guest_user()
    {
        $permission = 'update-something';
        
        $this->assertEquals('does not have permission', $this->renderView('can', ['permission' => $permission]));
        $this->assertEquals('does not belong to auth group', $this->renderView('authgroup', ['group' => 'admins']));
        $this->assertEquals('does not belong to auth group', $this->renderView('isoneof', ['group' => 'users']));
        $this->assertEquals('does not belong to team', $this->renderView('team', ['group' => 'writers']));
        $this->assertEquals('does not belong to any of the auth groups', $this->renderView('isoneofany', ['group' => 'admins,users']));
        $this->assertEquals('does not belong to all of the auth groups', $this->renderView('isoneofall', ['group' => ['admins','users']]));
    }

    protected function renderView(string $view, array $parameters)
    {
        Artisan::call('view:clear');

        $view = view($view)->with($parameters);

        return trim((string) $view);
    }
}