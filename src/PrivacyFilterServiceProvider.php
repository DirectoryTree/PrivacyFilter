<?php

namespace DirectoryTree\PrivacyFilter;

use DirectoryTree\PrivacyFilter\Commands\InstallBinaryCommand;
use DirectoryTree\PrivacyFilter\Commands\InstallCommand;
use DirectoryTree\PrivacyFilter\Commands\InstallModelCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Register and bootstrap the Privacy Filter package services.
 */
class PrivacyFilterServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/privacy-filter.php', 'privacy-filter');

        $this->app->singleton(Classifier::class, function () {
            return new Classifier(
                binaryPath: config('privacy-filter.paths.binary'),
                modelPath: config('privacy-filter.paths.model'),
                timeout: config('privacy-filter.process.timeout'),
            );
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/privacy-filter.php' => config_path('privacy-filter.php'),
            ], 'privacy-filter-config');

            $this->commands([
                InstallCommand::class,
                InstallBinaryCommand::class,
                InstallModelCommand::class,
            ]);
        }
    }
}
