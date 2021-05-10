<?php

namespace RenokiCo\CashierRegister\Test;

use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;
use RenokiCo\CashierRegister\Saas;
use Stripe\ApiResource;
use Stripe\Exception\InvalidRequestException;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Stripe;

abstract class TestCase extends Orchestra
{
    protected static $productId;

    protected static $freeProductId;

    protected static $stripePlanId;

    protected static $stripeFreePlanId;

    protected static $paddlePlanId;

    protected static $paddleFreePlanId;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        Saas::clearPlans();

        $this->resetDatabase();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->withFactories(__DIR__.'/database/factories');

        if (getenv('CASHIER_PACKAGE') === 'stripe') {
            Saas::plan('Monthly $10', static::$stripePlanId)
                ->features([
                    Saas::feature('Build Minutes', 'build.minutes', 3000),
                    Saas::feature('Seats', 'teams', 10)->notResettable(),
                ]);

            Saas::plan('Free Plan', static::$stripeFreePlanId)
                ->features([
                    Saas::feature('Build Minutes', 'build.minutes', 10),
                    Saas::feature('Seats', 'teams', 5)->notResettable(),
                ]);
        }

        if (getenv('CASHIER_PACKAGE') === 'paddle') {
            Saas::plan('Monthly $20', static::$paddlePlanId)
                ->features([
                    Saas::feature('Build Minutes', 'build.minutes', 3000),
                    Saas::feature('Seats', 'teams', 10)->notResettable(),
                ]);

            Saas::plan('Free Plan', static::$paddleFreePlanId)
                ->features([
                    Saas::feature('Build Minutes', 'build.minutes', 10),
                    Saas::feature('Seats', 'teams', 5)->notResettable(),
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (getenv('CASHIER_PACKAGE') === 'stripe') {
            Stripe::setApiKey(getenv('STRIPE_SECRET') ?: env('STRIPE_SECRET'));

            static::$stripePlanId = 'monthly-10-'.Str::random(10);

            static::$stripeFreePlanId = 'free-'.Str::random(10);

            static::$productId = 'product-1'.Str::random(10);

            static::$freeProductId = 'product-free'.Str::random(10);

            Product::create([
                'id' => static::$productId,
                'name' => 'Laravel Cashier Test Product',
                'type' => 'service',
            ]);

            Product::create([
                'id' => static::$freeProductId,
                'name' => 'Laravel Cashier Test Product',
                'type' => 'service',
            ]);

            Plan::create([
                'id' => static::$stripePlanId,
                'nickname' => 'Monthly $10',
                'currency' => 'USD',
                'interval' => 'month',
                'billing_scheme' => 'per_unit',
                'amount' => 1000,
                'product' => static::$productId,
            ]);

            Plan::create([
                'id' => static::$stripeFreePlanId,
                'nickname' => 'Free',
                'currency' => 'USD',
                'interval' => 'month',
                'billing_scheme' => 'per_unit',
                'amount' => 0,
                'product' => static::$freeProductId,
            ]);
        }

        if (getenv('CASHIER_PACKAGE') === 'paddle') {
            static::$paddlePlanId = getenv('PADDLE_TEST_PLAN') ?: env('PADDLE_TEST_PLAN');

            static::$paddleFreePlanId = getenv('PADDLE_TEST_FREE_PLAN') ?: env('PADDLE_TEST_FREE_PLAN');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (getenv('CASHIER_PACKAGE') === 'stripe') {
            static::deleteStripeResource(new Plan(static::$stripePlanId));
            static::deleteStripeResource(new Plan(static::$stripeFreePlanId));
        }
    }

    /**
     * Get the package providers.
     *
     * @param  mixed  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        $providers = [];

        if (getenv('CASHIER_PACKAGE') === 'stripe') {
            $providers[] = \Laravel\Cashier\CashierServiceProvider::class;
        }

        if (getenv('CASHIER_PACKAGE') === 'paddle') {
            $providers[] = \Laravel\Paddle\CashierServiceProvider::class;
        }

        return array_merge($providers, [
            \RenokiCo\CashierRegister\CashierRegisterServiceProvider::class,
        ]);
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
        $app['config']->set('auth.providers.users_with_stripe.model', Models\Stripe\User::class);
        $app['config']->set('auth.providers.users_with_paddle.model', Models\Paddle\User::class);
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

    protected static function deleteStripeResource(ApiResource $resource)
    {
        if (getenv('CASHIER_PACKAGE') === 'stripe') {
            try {
                $resource->delete();
            } catch (InvalidRequestException $e) {
                //
            }
        }
    }
}
