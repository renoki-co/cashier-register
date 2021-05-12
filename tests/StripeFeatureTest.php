<?php

namespace RenokiCo\CashierRegister\Test;

use Carbon\Carbon;
use RenokiCo\CashierRegister\Saas;
use RenokiCo\CashierRegister\Test\Models\Stripe\User;
use Stripe\Plan;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\ApiResource;
use Stripe\Exception\InvalidRequestException;

class StripeFeatureTest extends TestCase
{
    protected static $productId;

    protected static $stripeMonthlyPlanId;

    protected static $stripeMeteredPriceId;

    protected static $stripeYearlyPlanId;

    protected static $stripeFreePlanId;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

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
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $freeStripePlan = Saas::plan('Free Plan', static::$stripeFreePlanId, static::$stripeYearlyPlanId)
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

    /**
     * Create a new subscription.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \RenokiCo\CashierRegister\Plan  $plan
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createSubscription($user, $plan)
    {
        $subscription = $user->newSubscription('main', $plan->getId());
        $meteredFeatures = $plan->getMeteredFeatures();

        if (! $meteredFeatures->isEmpty()) {
            foreach ($meteredFeatures as $feature) {
                $subscription->meteredPlan($feature->getMeteredId());
            }
        }

        return $subscription->create('pm_card_visa');
    }

    public function test_record_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(50, $subscription->getUsedQuota('build.minutes'));

        $this->assertEquals(
            2950, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_set_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $subscription->setFeatureUsage('build.minutes', 20);

        $this->assertEquals(
            20, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            2980, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_reduce_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getUsedQuota('build.minutes')
        );

        $subscription->decrementFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_reduce_feature_usage_without_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->decrementFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_feature_usage_on_reset()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getUsedQuota('build.minutes')
        );

        $subscription->resetQuotas();

        $this->assertEquals(
            0, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_feature_usage_on_resetting_not_resettable()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('teams', 1);

        $this->assertEquals(
            1, $subscription->getUsedQuota('teams')
        );

        $subscription->resetQuotas();

        $this->assertEquals(
            1, $subscription->getUsedQuota('teams')
        );

        $this->assertEquals(
            9, $subscription->getRemainingQuota('teams')
        );
    }

    public function test_record_inexistent_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId)
            ->features([]);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            0, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            0, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_plan_with_feature_to_array()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $this->assertTrue(
            is_array($plan->toArray())
        );

        $this->assertTrue(
            is_array($plan->toArray()['features'])
        );
    }

    public function test_feature_usage_not_resettable()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('teams', 5);

        $this->assertEquals(
            5, $subscription->getUsedQuota('teams')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            5, $subscription->getUsedQuota('teams')
        );

        $this->assertEquals(
            5, $subscription->getRemainingQuota('teams')
        );
    }

    public function test_feature_usage_over_the_amount()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $overQuota = 'not_set';

        $subscription->recordFeatureUsage('teams', 11, true, function ($feature, $valueOverQuota, $subscription) use (&$overQuota) {
            $overQuota = $valueOverQuota;
        });

        $this->assertEquals(1, $overQuota);
    }

    public function test_feature_usage_over_the_amount_with_metering()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $overQuota = 'not_set';

        $subscription->recordFeatureUsage('metered.build.minutes', 4000, true, function ($feature, $valueOverQuota, $subscription) use (&$overQuota) {
            $overQuota = $valueOverQuota;
        });

        $this->assertEquals(1000, $overQuota);

        $this->assertEquals(
            3000, $subscription->getUsedQuota('metered.build.minutes')
        );

        $usage = $subscription->usageRecordsFor(static::$stripeMeteredPriceId)[0]->total_usage;

        $this->assertEquals(1000, $usage);

        // The new feature record should use only the metered billing.
        $subscription->recordFeatureUsage('metered.build.minutes', 4000, true, function ($feature, $valueOverQuota, $subscription) use (&$overQuota) {
            $overQuota = $valueOverQuota;
        });

        $this->assertEquals(4000, $overQuota);

        $usage = $subscription->usageRecordsFor(static::$stripeMeteredPriceId)[0]->total_usage;

        $this->assertEquals(5000, $usage);

        $this->assertEquals(
            3000, $subscription->getUsedQuota('metered.build.minutes')
        );
    }

    public function test_feature_usage_on_unlimited()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripeMonthlyPlanId)
            ->features([
                Saas::feature('Seats', 'teams')->unlimited()->notResettable(),
            ]);

        $subscription = $this->createSubscription($user, $plan);

        $overQuota = 0;

        $subscription->recordFeatureUsage('teams', 100, true, function ($feature, $valueOverQuota, $subscription)  use (&$overQuota) {
            $overQuota = 'set';
        });

        $this->assertEquals(
            100, $subscription->getUsedQuota('teams')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            100, $subscription->getUsedQuota('teams')
        );

        $this->assertEquals(
            -1, $subscription->getRemainingQuota('teams')
        );

        $this->assertEquals(0, $overQuota);
    }

    public function test_downgrading_plan()
    {
        $user = factory(User::class)->create();

        $freePlan = Saas::getPlan(static::$stripeFreePlanId);

        $paidPlan = Saas::getPlan(static::$stripeMonthlyPlanId);

        $subscription = $user->newSubscription('main', static::$stripeMonthlyPlanId)->create('pm_card_visa');

        $subscription->recordFeatureUsage('teams', 10);

        $overQuotaFeatures = $subscription->featuresOverQuotaWhenSwapping(
            static::$stripeFreePlanId
        );

        $this->assertCount(
            1, $overQuotaFeatures
        );

        $this->assertEquals(
            'teams', $overQuotaFeatures->first()->getId()
        );

        $subscription->swap($freePlan->getId());

        $this->assertTrue($subscription->featureOverQuota('teams'));
    }
}
