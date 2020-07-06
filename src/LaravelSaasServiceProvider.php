<?php

namespace RenokiCo\LaravelSaas;

use Illuminate\Support\ServiceProvider;

class LaravelSaasServiceProvider extends ServiceProvider
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
            __DIR__.'/../stubs/LaravelSaasServiceProvider.stub' => app_path('Providers/LaravelSaasServiceProvider.php'),
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
        //
    }
}
