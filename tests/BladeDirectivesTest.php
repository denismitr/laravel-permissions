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
    /** @var AuthGroup */
    private $authors;

    /** @var AuthGroup */
    private $bloggers;

    /** @var AuthGroup */
    private $writers;

    public function setUp(): void
    {
        parent::setUp();

        // Views for testing
        $finder = new \Illuminate\View\FileViewFinder(app()['files'], array(__DIR__ . '/views'));
        View::setFinder($finder);

        $this->authors = AuthGroup::create(['name' => 'authors']);
        $this->bloggers = AuthGroup::create(['name' => 'bloggers']);
        $this->writers = AuthGroup::create(['name' => 'writers']);

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
        $this->assertEquals('does not belong to all of the auth groups', $this->renderView('isoneofall', ['group' => ['admins', 'users']]));
    }

    /** @test */
    public function can_blade_directives_will_evaluate_to_true_if_logged_user_has_permission_via_auth_group()
    {
        // Given
        $permission = 'update-something';
        /** @var User $userA */
        $userA = User::create(['email' => 'test@test.com']);
        /** @var User $userB */
        $userB = User::create(['email' => 'test2@test.com']);

        // Assign permissions
        $this->authors->addUser($userA);
        $this->authors->givePermissionTo('update-something');

        $userB->joinAuthGroup($this->writers);
        $userB->onAuthGroup($this->writers)->givePermissionTo('delete-something');

        // Do login
        $this->be($userA);

        // Expect user to have a permission
        $this->assertEquals('has permission', $this->renderView('can', ['permission' => 'update-something']));

        $this->be($userB);

        $this->assertEquals('has permission', $this->renderView('can', ['permission' => 'delete-something']));
    }

    /** @test */
    public function auth_group_team_and_other_aliases_will_evaluate_to_true_when_user_belongs_to_authgroup()
    {
        /** @var User $userA */
        $userA = User::create(['email' => 'test@test.com']);
        /** @var User $userB */
        $userB = User::create(['email' => 'test2@test.com']);

        // Assign permissions
        $this->authors->addUser($userA);
        $this->authors->givePermissionTo('update-something');

        $userB->joinAuthGroup($this->writers);
        $userB->joinAuthGroup($this->bloggers);

        $this->be($userA);

        $this->assertEquals('belongs to auth group', $this->renderView('authgroup', ['group' => 'authors']));
        $this->assertEquals('belongs to team', $this->renderView('team', ['team' => 'authors']));
        $this->assertEquals('belongs to auth group', $this->renderView('isoneof', ['group' => 'authors']));
        $this->assertEquals('belongs to one of the auth groups', $this->renderView('isoneofany', ['group' => 'authors']));
        $this->assertEquals('belongs to all of the auth groups', $this->renderView('isoneofall', ['group' => 'authors']));

        $this->be($userB);

        $this->assertEquals('belongs to auth group', $this->renderView('authgroup', ['group' => 'writers']));
        $this->assertEquals('belongs to team', $this->renderView('team', ['team' => 'bloggers']));
        $this->assertEquals('belongs to auth group', $this->renderView('isoneof', ['group' => 'writers|bloggers']));
        $this->assertEquals('belongs to one of the auth groups', $this->renderView('isoneofany', ['group' => 'authors,writers,bloggers,admins']));
        $this->assertEquals('belongs to all of the auth groups', $this->renderView('isoneofall', ['group' => ['writers', 'bloggers']]));
    }

    protected function renderView(string $view, array $parameters)
    {
        Artisan::call('view:clear');

        $view = view($view)->with($parameters);

        return trim((string)$view);
    }
}
