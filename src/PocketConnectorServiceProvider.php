<?php

namespace Skydiver\PocketConnector;

use Illuminate\Support\ServiceProvider;
use Skydiver\PocketConnector\Console\Tags;
use Skydiver\PocketConnector\Console\Import;

class PocketConnectorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'pocket-connector');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'pocket-connector');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('pocket-connector.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pocket-connector'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/pocket-connector'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/pocket-connector'),
            ], 'lang');*/

            // Registering package commands.
            $this->commands([
                Import::class,
                Tags::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'pocket-connector');

        // Register the main class to use with the facade
        $this->app->singleton('pocket-connector', function () {
            return new PocketConnector;
        });
    }
}
