<?php

namespace RenokiCo\Fuel\Test;

use Carbon\Carbon;
use RenokiCo\Fuel\Exceptions\FeatureUsageOverflowException;
use RenokiCo\Fuel\Saas;
use RenokiCo\Fuel\Test\Models\User;

class FeatureTest extends TestCase
{
    public function test_record_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getFeatureUsage('build.minutes')
        );

        $this->assertEquals(
            2950, $subscription->getFeatureRemainings('build.minutes')
        );
    }

    public function test_reduce_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getFeatureUsage('build.minutes')
        );

        $subscription->reduceFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getFeatureRemainings('build.minutes')
        );
    }

    public function test_reduce_feature_usage_without_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->reduceFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getFeatureRemainings('build.minutes')
        );
    }

    public function test_feature_usage_on_plan_change()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $newPlan = Saas::plan('Plan', 'new-plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getFeatureUsage('build.minutes')
        );

        $subscription->changePlan($newPlan);

        $this->assertEquals(
            3000, $subscription->getFeatureRemainings('build.minutes')
        );
    }

    public function test_feature_usage_on_plan_renew()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getFeatureUsage('build.minutes')
        );

        $subscription->renew();

        $this->assertEquals(
            0, $subscription->getFeatureUsage('build.minutes')
        );

        $this->assertEquals(
            3000, $subscription->getFeatureValue('build.minutes')
        );
    }

    public function test_feature_usage_on_reset()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getFeatureUsage('build.minutes')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            0, $subscription->getFeatureUsage('build.minutes')
        );

        $this->assertEquals(
            3000, $subscription->getFeatureRemainings('build.minutes')
        );
    }

    public function test_record_inexistent_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            0, $subscription->getFeatureUsage('build.minutes')
        );

        $this->assertEquals(
            0, $subscription->getFeatureRemainings('build.minutes')
        );
    }

    public function test_plan_with_feature_to_array()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);

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

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Team Members', 'teams', 10)->notResettable(),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('teams', 5);

        $this->assertEquals(
            5, $subscription->getFeatureUsage('teams')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            5, $subscription->getFeatureUsage('teams')
        );

        $this->assertEquals(
            5, $subscription->getFeatureRemainings('teams')
        );
    }

    public function test_feature_usage_over_the_amount()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Team Members', 'teams', 10)->notResettable(),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->expectException(FeatureUsageOverflowException::class);

        $subscription->recordFeatureUsage('teams', 11);
    }

    public function test_feature_usage_on_unlimited()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->features([
                Saas::feature('Team Members', 'teams')->notResettable()->unlimited(),
            ]);

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->recordFeatureUsage('teams', 100);

        $this->assertEquals(
            100, $subscription->getFeatureUsage('teams')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            100, $subscription->getFeatureUsage('teams')
        );

        $this->assertEquals(
            -1, $subscription->getFeatureRemainings('teams')
        );
    }
}
