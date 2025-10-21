<?php

namespace HasanHawary\DynamicCli;

use HasanHawary\DynamicCli\Console\CrudMakeCommand;
use Illuminate\Support\ServiceProvider;

class DynamicCliServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish the default dynamic-cli-dynamic-cli config
        $this->publishes([
            __DIR__ . '/../config/dynamic-cli.php' => config_path('dynamic-cli-dynamic-cli'),
        ], 'dynamic-cli-dynamic-cli-config');

        // Register package commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudMakeCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/dynamic-cli.php', 'dynamic-cli-dynamic-cli');
    }
}
