<?php

namespace HasanHawary\DynamicCli;

use HasanHawary\DynamicCli\Console\DBuildCommand;
use HasanHawary\DynamicCli\Console\DCrudMakeCommand;
use Illuminate\Support\ServiceProvider;

class DynamicCliServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish the default dynamic-cli config
        $this->publishes([
            __DIR__ . '/../config/dynamic-cli.php' => config_path('dynamic-cli.php'),
        ], 'dynamic-cli-config');

        // Register package commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DBuildCommand::class,
                DCrudMakeCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/dynamic-cli.php', 'dynamic-cli');

        // Load any helpers if added in the future
        if (file_exists(__DIR__ . '/helpers.php')) {
            require_once __DIR__ . '/helpers.php';
        }
    }
}
