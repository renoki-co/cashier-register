<?php

namespace RenokiCo\Fuel;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class FuelServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/saas.php' => config_path('saas.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../stubs/FuelServiceProvider.stub' => app_path('Providers/FuelServiceProvider.php'),
        ], 'horizon-provider');

        $this->mergeConfigFrom(
            __DIR__.'/../config/saas.php', 'saas'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Cashier::ignoreMigrations();
    }
}
