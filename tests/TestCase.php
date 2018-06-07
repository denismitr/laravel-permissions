<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\Role;
use Denismitr\Permissions\PermissionsServiceProvider;
use Denismitr\Permissions\PermissionLoader;
use Denismitr\Permissions\Test\Models\Admin;
use Denismitr\Permissions\Test\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Admin
     */
    protected $admin;

    /**
     * @var Role
     */
    protected $userRole;

    /**
     * @var Role
     */
    protected $premiumRole;

    /**
     * @var Role
     */
    protected $adminRole;

    /**
     * @var Permission
     */
    protected $adminPermission;

    /**
     * @var Permission
     */
    protected $editArticlesPermission;

    /**
     * @var Permission
     */
    protected $editNewsPermission;

    /**
     * @var Permission
     */
    protected $editBlogPermission;


    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->reloadPermissions();
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionsServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set-up admin guard
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => Admin::class]);

        // Use test User model for users provider
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUpDatabase(Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        $app['db']->connection()->getSchemaBuilder()->create('admins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        include_once __DIR__.'/../migrations/create_roles_table.php';
        include_once __DIR__.'/../migrations/create_permissions_table.php';
        include_once __DIR__.'/../migrations/create_user_roles_table.php';
        include_once __DIR__.'/../migrations/create_user_permissions_table.php';
        include_once __DIR__.'/../migrations/create_role_permissions_table.php';

        (new \CreateRolesTable())->up();
        (new \CreatePermissionsTable())->up();
        (new \CreateUserRolesTable())->up();
        (new \CreateUserPermissionsTable())->up();
        (new \CreateRolePermissionsTable())->up();

        $this->user = User::create(['email' => 'test@user.com']);
        $this->admin = Admin::create(['email' => 'admin@user.com']);

        $this->userRole = $app[Role::class]->create(['name' => 'ROLE_USER']);
        $this->premiumRole = $app[Role::class]->create(['name' => 'ROLE_PREMIUM']);
        $this->adminRole = $app[Role::class]->create(['name' => 'ROLE_ADMIN', 'guard' => 'admin']);

        $this->editArticlesPermission = $app[Permission::class]->create(['name' => 'edit-articles']);
        $this->editNewsPermission = $app[Permission::class]->create(['name' => 'edit-news']);
        $this->editBlogPermission = $app[Permission::class]->create(['name' => 'edit-blog']);
        $this->adminPermission = $app[Permission::class]->create(['name' => 'admin-actions', 'guard' => 'admin']);
    }

    /**
     * Reload the permissions.
     */
    protected function reloadPermissions()
    {
        app(PermissionLoader::class)->forgetCachedPermissions();
    }

    /**
     * Refresh the testUser.
     */
    public function refreshUser()
    {
        $this->user = $this->user->fresh();
    }

    /**
     * Refresh the testAdmin.
     */
    public function refreshAdmin()
    {
        $this->admin = $this->admin->fresh();
    }

    /**
     * Refresh the testAdmin.
     */
    public function refreshUserRole()
    {
        $this->userRole = $this->userRole->fresh();
    }
}