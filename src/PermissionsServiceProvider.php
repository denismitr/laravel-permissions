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
			Permission::get()->map(function ($permission) {
				Gate::define($permission->name, function ($user) use ($permission) {
					return $user->hasPermissionTo($permission);
				});
			});
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
        Blade::directive('authgroup', function($group) {
            return "<?php if(auth()->check() && auth()->user()->isOneOf({$group})): ?>";
        });

        Blade::directive('endauthgroup', function() {
            return "<?php endif; ?>";
        });

        Blade::directive('isoneof', function($group) {
            return "<?php if(auth()->check() && auth()->user()->isOneOf({$group})): ?>";
        });

        Blade::directive('endisoneof', function() {
            return "<?php endif; ?>";
        });

        Blade::directive('team', function($group) {
            return "<?php if(auth()->check() && auth()->user()->isOneOf({$group})): ?>";
        });

        Blade::directive('endteam', function() {
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
