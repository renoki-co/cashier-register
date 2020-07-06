<?php

namespace RenokiCo\LaravelSaas\Test;

use Carbon\Carbon;
use RenokiCo\LaravelSaas\Saas;
use RenokiCo\LaravelSaas\Plan;
use RenokiCo\LaravelSaas\Test\Models\User;
use RenokiCo\LaravelSaas\Exceptions\PlanArchivedException;

class SubscriptionTest extends TestCase
{
    public function test_plan_subscription_on_archived_plan()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->archive();

        $this->expectException(PlanArchivedException::class);

        $subscription = $user->newSaasSubscription('main', $plan);
    }

    public function test_plan_subscription_on_plan_with_trial()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly()
            ->trial(7, 'day');

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertEquals(
            'plan', $subscription->getPlan()->getId()
        );

        $this->assertTrue(
            $subscription->model->is($user)
        );

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        $this->assertTrue($subscription->active());
        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());

        Carbon::setTestNow(now()->addDays(7));

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );

        Carbon::setTestNow(now()->addMonths(1)->subDays(1));

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );

        Carbon::setTestNow(now()->addDays(1));

        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertTrue($subscription->ended());

        $this->assertFalse(
            $user->subscribedToSaasPlan($plan)
        );
    }

    public function test_plan_subscription_on_plan_without_trial()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());

        Carbon::setTestNow(now()->addMonths(1)->subDays(1));

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );

        Carbon::setTestNow(now()->addDays(1));

        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertTrue($subscription->ended());

        $this->assertFalse(
            $user->subscribedToSaasPlan($plan)
        );
    }

    public function test_cancel_immediately()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(7));

        $subscription->cancel(true);

        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->canceled());
        $this->assertTrue($subscription->ended());

        $this->assertFalse(
            $user->subscribedToSaasPlan($plan)
        );
    }

    public function test_cancel_immediately_with_trial()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(3));

        $subscription->cancel(true);

        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->canceled());
        $this->assertTrue($subscription->ended());

        $this->assertFalse(
            $user->subscribedToSaasPlan($plan)
        );
    }

    public function test_cancel_immediately_with_trial_should_not_recreate_trial()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(3));

        $subscription->cancel(true);

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());
    }

    public function test_cancel()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->invoice(10, 'day');

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(7));

        $subscription->cancel();

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->canceled());
        $this->assertFalse($subscription->ended());

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );
    }

    public function test_cancel_with_trial()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->invoice(10, 'day')
            ->trial(7, 'day');

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(3));

        $subscription->cancel();

        $this->assertTrue($subscription->active());
        $this->assertTrue($subscription->onTrial());
        $this->assertTrue($subscription->canceled());
        $this->assertFalse($subscription->ended());

        $this->assertTrue(
            $user->subscribedToSaasPlan($plan)
        );
    }

    public function test_cancel_with_trial_should_not_recreate_trial()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->invoice(10, 'day')
            ->trial(7, 'day');

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(3));

        $subscription->cancel();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());
    }

    public function test_change_plan()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $newPlan = Saas::plan('Plan', 'new-plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        $subscription->changePlan($newPlan);

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());

        $this->assertFalse(
            $user->subscribedToSaasPlan($plan)
        );

        $this->assertTrue(
            $user->subscribedToSaasPlan($newPlan)
        );

        $this->assertEquals(
            'new-plan', $subscription->getPlan()->getId()
        );
    }

    public function test_renew_plan()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $this->assertTrue(
            $user->hadTrialFor($plan)
        );

        Carbon::setTestNow(now()->addDays(3));

        $subscription->renew();

        Carbon::setTestNow(now()->addDays(7));

        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->ended());
    }

    public function test_dont_renew_plan_if_ended()
    {
        $user = factory(User::class)->create();

        $plan = Saas::plan('Plan', 'plan')
            ->monthly();

        $subscription = $user->newSaasSubscription('main', $plan);

        $subscription->cancel(true);

        $subscription->renew();

        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->canceled());
        $this->assertTrue($subscription->ended());
    }
}
