<?php

namespace Severite\Providers;

use Severite\Console\Commands\CreateSeveriteMigrationCommand;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Severite\Console\Commands\InstallSeveriteCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Severite\Http\Middleware\SeveriteInertiaMiddleware;
use SeveriteScript\ComposerScripts;

class SeveriteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/severite.php', 'severite');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->runningInConsole();
        $this->registerCommand();
        $this->registerRoutes();

        $this->publishes([
            __DIR__.'/../../config/severite.php' => config_path('severite.php'),
        ], 'severite-config');

        $this->publishes([
            __DIR__.'/../../database/migrations/2026_03_25_084626_create_xhprof_report.php' => database_path('migrations/2026_03_25_084626_create_xhprof_report.php'),
        ], 'severite-migration');

        $this->publishes([
            __DIR__.'/../../resources/views/severite.blade.php' => resource_path('views/severite.blade.php'),
        ], 'severite-blade');

        $this->publishes([
            __DIR__.'/../../public/build' => public_path('build/severite'),
        ], 'severite-assets');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'severite');
    }

    public function runningInConsole()
    {
        if ($this->app->runningInConsole()) {
            //todo:  Affiche le message une seule fois après installation
            //todo voir sino pour couper les messages speicifque pour chaque partie
            if (! file_exists(config_path('severite.php'))) {
                ComposerScripts::postInstall();
            }
        }
    }

    public function registerCommand()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSeveriteMigrationCommand::class,
                InstallSeveriteCommand::class,
            ]);
        }
    }

    /**
     * Register the routes.
     */
    protected function registerRoutes(): void
    {
        // Évite de recharger si les routes sont en cache (comme Horizon le fait)
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        Route::group([
            'prefix'     => config('severite.path', 'severite'),
            'namespace'  => 'Severite\Http\Controllers\ReportController',
            'middleware' => [SeveriteInertiaMiddleware::class],
            'as'         => 'severite.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        });
    }
}
