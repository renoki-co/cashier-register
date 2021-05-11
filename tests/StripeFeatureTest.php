<?php

namespace RenokiCo\CashierRegister\Test;

use Carbon\Carbon;
use RenokiCo\CashierRegister\Saas;
use RenokiCo\CashierRegister\Test\Models\Stripe\User;

class StripeFeatureTest extends TestCase
{
    public function test_record_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            2950, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_set_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

        $subscription->decrementFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_feature_usage_on_reset()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $plan = Saas::getPlan(static::$stripePlanId)
            ->features([]);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $plan = Saas::getPlan(static::$stripePlanId);

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

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $plan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

        $overQuota = 'not_set';

        $subscription->recordFeatureUsage('teams', 11, true, function ($feature, $valueOverQuota, $subscription) use (&$overQuota) {
            $overQuota = $valueOverQuota;
        });

        $this->assertTrue($subscription->featureOverQuota('teams'));
        $this->assertEquals(1, $overQuota);
    }

    public function test_feature_usage_on_unlimited()
    {
        $user = factory(User::class)->create();

        $plan = Saas::getPlan(static::$stripePlanId)
            ->features([
                Saas::feature('Seats', 'teams')->unlimited()->notResettable(),
            ]);

        $subscription = $user->newSubscription('main', $plan->getId())->create('pm_card_visa');

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

        $paidPlan = Saas::getPlan(static::$stripePlanId);

        $subscription = $user->newSubscription('main', static::$stripePlanId)->create('pm_card_visa');

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
    }
}
