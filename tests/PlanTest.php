<?php

namespace RenokiCo\CashierRegister\Test;

use RenokiCo\CashierRegister\Saas;

class PlanTest extends TestCase
{
    public function test_build_plans()
    {
        Saas::clearPlans();

        Saas::plan('Active Plan', 'plan')
            ->price(10, 'USD')
            ->description('Some plan...')
            ->data(['somekey' => 'someval'])
            ->popular();

        Saas::plan('Archived Plan', 'archived-plan')
            ->price(15, 'USD')
            ->deprecated();

        $this->assertCount(1, Saas::getAvailablePlans());

        $plan = Saas::getPlan('plan');

        $this->assertEquals('plan', $plan->getId());

        $this->assertEquals(['somekey' => 'someval'], $plan->getData());
    }

    public function test_build_plans_with_features()
    {
        Saas::clearPlans();

        $plan = Saas::plan('Active Plan', 'plan')
            ->description('Some plan...')
            ->features([
                Saas::feature('Build Minutes', 'build.minutes')
                    ->description('Build minutes for all your projects.')
                    ->value(100)
                    ->data(['os' => 'linux']),
            ]);

        $this->assertCount(1, $plan->getFeatures());

        $feature = $plan->getFeature('build.minutes');

        $this->assertEquals(['os' => 'linux'], $feature->getData());
    }
}
