<?php

namespace HasanHawary\DynamicCli;

use HasanHawary\DynamicCli\Console\DCrudMakeCommand;
use Illuminate\Support\ServiceProvider;

class DynamicCliServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish the default dynamic-dynamic config
        $this->publishes([
            __DIR__ . '/../config/dynamic-cli.php' => config_path('dynamic-dynamic'),
        ], 'dynamic-dynamic-config');

        // Register package commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DCrudMakeCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/dynamic-cli.php', 'dynamic-dynamic');
    }
}
