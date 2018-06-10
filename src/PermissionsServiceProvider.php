<?php

namespace Denismitr\Permissions;

use Gate;
use Denismitr\Permissions\Models\Permission;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param PermissionLoader $permissionLoader
     * @return void
     */
    public function boot(PermissionLoader $permissionLoader)
    {
        if ( ! is_lumen()) {
            $this->publishes([
                __DIR__.'/../config/permissions.php' => config_path('permissions.php'),
            ], 'config');

            $this->publishMigrations();
        }
		
		
		try {			
//			Permission::get()->map(function ($permission) {
//				Gate::define($permission->name, function ($user) use ($permission) {
//					return $user->hasPermissionTo($permission);
//				});
//			});
		} catch(\Throwable $t) {}


        $permissionLoader->registerPermissions();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if ( ! is_lumen()) {
            $this->mergeConfigFrom(
                __DIR__ . './../config/permissions.php',
                'permissions'
            );
        }

        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives()
    {
        Blade::directive('role', function($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', function() {
            return "<?php endif; ?>";
        });
    }

    protected function publishMigrations()
    {
        $timestamp = date('Y_m_d_His', time());

        $this->publishes([
            __DIR__ . './../migrations/create_laravel_permissions.php' =>
                database_path("/migrations/{$timestamp}_create_laravel_permissions.php"),
        ]);
    }
}
