<?php


namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Models\Permission;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\PermissionsServiceProvider;
use Denismitr\Permissions\Loader;
use Denismitr\Permissions\Test\Models\Admin;
use Denismitr\Permissions\Test\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PHPUnit\Framework\Assert;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var User
     */
    protected $admin;

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

    /**
     * @var AuthGroup
     */
    protected $usersGroup;

    /**
     * @var AuthGroup
     */
    protected $adminsGroup;

    /**
     * @var Permission
     */
    protected $blogAdminPermission;


    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->reloadPermissions();

        $this->createMacros();
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

        // Use test User model for users provider
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permissions.models.user', User::class);
    }

    protected function setUpDatabase(Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        include_once __DIR__.'/../migrations/create_laravel_permissions.php';

        (new \CreateLaravelPermissions())->up();

        $this->user = User::create(['email' => 'user@test.com']);
        $this->admin = User::create(['email' => 'admin@test.com']);

        $this->usersGroup = $app[AuthGroup::class]->create(['name' => 'users']);
        $this->adminsGroup = $app[AuthGroup::class]->create(['name' => 'admins']);

        $this->editArticlesPermission = $app[Permission::class]->create(['name' => 'edit-articles']);
        $this->editNewsPermission = $app[Permission::class]->create(['name' => 'edit-news']);
        $this->editBlogPermission = $app[Permission::class]->create(['name' => 'edit-blog']);
        $this->adminPermission = $app[Permission::class]->create(['name' => 'administrate-website']);
        $this->blogAdminPermission = $app[Permission::class]->create(['name' => 'administrate-blog']);
    }

    /**
     * Reload the permissions.
     */
    protected function reloadPermissions()
    {
        app(Loader::class)->forgetCachedPermissions();
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

    protected function createMacros()
    {
        Collection::macro('assertContains', function($value) {
            Assert::assertTrue(
                $this->contains($value),
                "Failed asserting that the collection contains the specified value."
            );
        });

        Collection::macro('assertNotContains', function($value) {
            Assert::assertFalse(
                $this->contains($value),
                "Failed asserting that the collection does not contain the specified value."
            );
        });

        Collection::macro('assertSame', function($items) {
            Assert::assertEquals(count($this), count($items));
            $this->zip($items)->each(function($pair) {
                [$a, $b] = $pair;
                Assert::assertTrue($a->is($b));
            });
        });

        Collection::macro('assertHasAll', function($items) {
            Assert::assertTrue(count($this) >= count($items));
            $items->each(function($item) use ($items) {
                Assert::assertTrue($this->contains($item));
            });
        });

        Collection::macro('assertCount', function($count) {
            Assert::assertEquals($count, count($this));
        });
    }
}