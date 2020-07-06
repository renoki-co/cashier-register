<?php

namespace RenokiCo\CashierRegister;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class CashierRegisterServiceProvider extends ServiceProvider
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
            __DIR__.'/../stubs/CashierRegisterServiceProvider.stub' => app_path('Providers/CashierRegisterServiceProvider.php'),
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
