<?php

namespace RenokiCo\LaravelSaas\Test;

use Orchestra\Testbench\TestCase as Orchestra;
use RenokiCo\LaravelSaas\Saas;

abstract class TestCase extends Orchestra
{
    /**
     * Set up the tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Saas::clearPlans();

        $this->resetDatabase();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->withFactories(__DIR__.'/database/factories');
    }

    /**
     * Get the package providers.
     *
     * @param  mixed  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \RenokiCo\LaravelSaas\LaravelSaasServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param  mixed  $app
     * @return void
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');
    }

    /**
     * Reset the database.
     *
     * @return void
     */
    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }
}
