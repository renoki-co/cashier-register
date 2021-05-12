<?php

namespace RenokiCo\CashierRegister\Test;

use Stripe\Plan;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\ApiResource;
use Illuminate\Support\Str;
use RenokiCo\CashierRegister\Saas;
use Stripe\Exception\InvalidRequestException;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected static $productId;

    protected static $stripeMonthlyPlanId;

    protected static $stripeMeteredPriceId;

    protected static $stripeYearlyPlanId;

    protected static $stripeFreePlanId;

    protected static $paddlePlanId;

    protected static $paddleFreePlanId;

    protected static $paddleYearlyPlanId;

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

        $freeStripePlan = Saas::plan('Free Plan', static::$stripeFreePlanId, static::$stripeFreePlanId)
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 10),
                Saas::feature('Seats', 'teams', 5)->notResettable(),
            ]);

        $freePaddlePlan = Saas::plan('Free Plan', static::$paddleFreePlanId, static::$paddleFreePlanId)
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 10),
                Saas::feature('Seats', 'teams', 5)->notResettable(),
            ]);

        Saas::plan('Monthly $10', static::$stripeMonthlyPlanId)
            ->inheritFeaturesFromPlan($freeStripePlan, [
                Saas::feature('Build Minutes', 'build.minutes', 3000),
                Saas::meteredFeature('Metered Build Minutes', 'metered.build.minutes', 3000)
                    ->meteredPrice(static::$stripeMeteredPriceId, 0.1, 'minute'),
                Saas::feature('Seats', 'teams', 10)->notResettable(),
            ]);

        Saas::plan('Yearly $100', static::$stripeYearlyPlanId)
            ->inheritFeaturesFromPlan($freeStripePlan, [
                Saas::feature('Build Minutes', 'build.minutes')->unlimited(),
                Saas::feature('Seats', 'teams', 10)->notResettable(),
            ]);

        Saas::plan('Monthly $20', static::$paddlePlanId)
            ->inheritFeaturesFromPlan($freePaddlePlan, [
                Saas::feature('Build Minutes', 'build.minutes', 3000),
                Saas::feature('Seats', 'teams', 10)->notResettable(),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::configureStripe();
        static::configurePaddle();
    }

    /**
     * Configure Stripe.
     *
     * @return void
     */
    protected static function configureStripe()
    {
        Stripe::setApiKey(getenv('STRIPE_SECRET') ?: env('STRIPE_SECRET'));

        static::$productId = Product::create(['name' => 'Demo Product'])->id;

        static::$stripeMonthlyPlanId = Plan::create([
            'nickname' => 'Monthly $10',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 1000,
            'product' => static::$productId,
        ])->id;

        static::$stripeYearlyPlanId = Plan::create([
            'nickname' => 'Yearly $100',
            'currency' => 'USD',
            'interval' => 'year',
            'billing_scheme' => 'per_unit',
            'amount' => 10000,
            'product' => static::$productId,
        ])->id;

        static::$stripeFreePlanId = Plan::create([
            'nickname' => 'Free',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 0,
            'product' => static::$productId,
        ])->id;

        static::$stripeMeteredPriceId = Price::create([
            'nickname' => 'Monthly Metered $0.01 per unit',
            'currency' => 'USD',
            'recurring' => [
                'interval' => 'month',
                'usage_type' => 'metered',
            ],
            'unit_amount' => 1,
            'product' => static::$productId,
        ])->id;
    }

    /**
     * Configure Paddle.
     *
     * @return void
     */
    protected static function configurePaddle()
    {
        static::$paddlePlanId = getenv('PADDLE_TEST_PLAN') ?: env('PADDLE_TEST_PLAN');
        static::$paddleYearlyPlanId = getenv('PADDLE_YEARLY_TEST_PLAN') ?: env('PADDLE_YEARLY_TEST_PLAN');
        static::$paddleFreePlanId = getenv('PADDLE_TEST_FREE_PLAN') ?: env('PADDLE_TEST_FREE_PLAN');
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        static::deleteStripeResource(new Plan(static::$stripeMonthlyPlanId));
        static::deleteStripeResource(new Plan(static::$stripeYearlyPlanId));
        static::deleteStripeResource(new Plan(static::$stripeFreePlanId));
        static::deleteStripeResource(new Product(static::$productId));
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
            \Laravel\Cashier\CashierServiceProvider::class,
            \Laravel\Paddle\CashierServiceProvider ::class,
            \RenokiCo\CashierRegister\CashierRegisterServiceProvider::class,
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

    /**
     * Delete the given Stripe resource.
     *
     * @param  \Stripe\ApiResource  $resource
     * @return void
     */
    protected static function deleteStripeResource(ApiResource $resource)
    {
        try {
            $resource->delete();
        } catch (InvalidRequestException $e) {
            //
        }
    }
}
