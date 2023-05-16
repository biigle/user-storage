<?php

namespace Biigle\Modules\UserStorage;

use Biigle\Http\Requests\UpdateUserSettings;
use Biigle\Modules\UserStorage\Console\Commands\CheckExpiredStorageRequests;
use Biigle\Modules\UserStorage\Console\Commands\PruneExpiredStorageRequests;
use Biigle\Modules\UserStorage\Console\Commands\PruneStaleStorageRequests;
use Biigle\Modules\UserStorage\Observers\UserObserver;
use Biigle\Modules\UserStorage\Support\FilesystemManager;
use Biigle\Services\Modules;
use Biigle\User;
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
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
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
                'adminShowUser',
                'dashboardButtons',
                'filesManual',
                'navbarMenuItem',
                'storageMenu',
            ],
            'controllerMixins' => [
                //
            ],
            'apidoc' => [
               __DIR__.'/Http/Controllers/Api/',
            ],
        ]);

        if (config('user_storage.notifications.allow_user_settings')) {
            $modules->registerViewMixin('user-storage', 'settings.notifications');
            UpdateUserSettings::addRule('storage_request_notifications', 'filled|in:email,web');
        }

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

        // Override gate to allow own user disk.
        $abilities = Gate::abilities();
        if (array_key_exists('use-disk', $abilities)) {
            $useDiskAbility = $abilities['use-disk'];
            Gate::define('use-disk', function (User $user, $disk) use ($useDiskAbility) {
                if (preg_match('/^user-[0-9]+$/', $disk)) {
                    if ($disk === "user-{$user->id}" || $user->can('sudo')) {
                        return true;
                    }
                }

                return $useDiskAbility($user, $disk);
            });
        }

        User::observe(new UserObserver);

        // This is used to resolve dynamic "user-xxx" storage disks.
        Storage::addConfigResolver(function ($name) {
            if (preg_match('/^user-[0-9]+$/', $name) === 1) {
                $diskName = config('user_storage.storage_disk');
                $config = $this->app['config']["filesystems.disks.{$diskName}"] ?: [];
                if (array_key_exists('root', $config)) {
                    $config['root'] .= '/'.$name;
                } else {
                    $config['root'] = $name;
                }

                if (array_key_exists('url', $config)) {
                    $config['url'] .= '/'.$name;
                }

                return $config;
            }

            return null;
        });
    }

    /**
    * Register the service provider.
    *
    * @return  void
    */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/user_storage.php', 'user_storage');
    }
}
