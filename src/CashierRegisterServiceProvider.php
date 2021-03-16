<?php

namespace RenokiCo\CashierRegister;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier as StripeCashier;
use Laravel\Paddle\Cashier as PaddleCashier;

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
        ], 'provider');

        $this->mergeConfigFrom(
            __DIR__.'/../config/saas.php', 'saas'
        );

        if (class_exists(StripeCashier::class)) {
            StripeCashier::useSubscriptionModel(config('saas.cashier.models.subscription.stripe'));
        }

        if (class_exists(PaddleCashier::class)) {
            PaddleCashier::useSubscriptionModel(config('saas.cashier.models.subscription.paddle'));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
