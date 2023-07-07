<?php

namespace Larry\Larry;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larry\Larry\Console\SetupCommand;

class LarryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerRoutes();
    }

    private function registerMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupCommand::class,
            ]);
        }
    }

    private function registerRoutes()
    {
        Route::group([
            'as' => 'api.larry.',
            'prefix' => 'api/larry',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }
}
