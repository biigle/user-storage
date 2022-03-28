<?php

namespace Biigle\Modules\UserStorage;

use Biigle\Modules\UserStorage\Console\Commands\CheckExpiredStorageRequests;
use Biigle\Modules\UserStorage\Console\Commands\PruneExpiredStorageRequests;
use Biigle\Modules\UserStorage\Console\Commands\PruneStaleStorageRequests;
use Biigle\Modules\UserStorage\Support\FilesystemManager;
use Biigle\Services\Modules;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Storage;

class UserStorageServiceProvider extends ServiceProvider
{

   /**
   * Bootstrap the application events.
   *
   * @param Modules $modules
   * @param  Router  $router
   * @return  void
   */
    public function boot(Modules $modules, Router $router)
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'user-storage');

        $router->group([
            'namespace' => 'Biigle\Modules\UserStorage\Http\Controllers',
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/Http/routes.php';
        });

        $modules->register('user-storage', [
            'viewMixins' => [
                'adminIndex',
                'dashboardButtons',
                'navbarMenuItem',
            ],
            'controllerMixins' => [
                //
            ],
            'apidoc' => [
               __DIR__.'/Http/Controllers/Api/',
            ],
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckExpiredStorageRequests::class,
                PruneExpiredStorageRequests::class,
                PruneStaleStorageRequests::class,
            ]);

            $this->app->booted(function () {
                $schedule = app(Schedule::class);
                $schedule->command(CheckExpiredStorageRequests::class)
                    ->daily()
                    ->onOneServer();

                $schedule->command(PruneExpiredStorageRequests::class)
                    ->daily()
                    ->onOneServer();

                $schedule->command(PruneStaleStorageRequests::class)
                    ->daily()
                    ->onOneServer();
            });
        }

        $this->publishes([
            __DIR__.'/public/assets' => public_path('vendor/user-storage'),
        ], 'public');

        Gate::policy(StorageRequest::class, Policies\StorageRequestPolicy::class);
    }

    /**
    * Register the service provider.
    *
    * @return  void
    */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/user_storage.php', 'user_storage');
        Storage::swap(new FilesystemManager($this->app));
    }
}
