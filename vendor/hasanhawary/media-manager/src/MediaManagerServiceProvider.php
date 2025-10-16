<?php

namespace HasanHawary\MediaManager;

use Illuminate\Support\ServiceProvider;

class MediaManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the service into the container
        $this->app->singleton(MediaManager::class, function ($app) {
            return new MediaManager();
        });
    }
}