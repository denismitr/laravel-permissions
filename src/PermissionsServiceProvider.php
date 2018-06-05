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
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
		
		
		try {			
			Permission::get()->map(function ($permission) {
				Gate::define($permission->name, function ($user) use ($permission) {
					return $user->hasPermissionTo($permission);
				});
			});
		} catch(\Throwable $t) {}

        Blade::directive('role', function($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', function() {
            return "<?php endif; ?>";
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
