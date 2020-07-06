<?php

namespace RenokiCo\Fuel\Test;

use RenokiCo\Fuel\Saas;

class PlanTest extends TestCase
{
    public function test_build_plans()
    {
        Saas::clearPlans();

        Saas::plan('Active Plan', 'plan')
            ->price(10, 'USD')
            ->description('Some plan...');

        Saas::plan('Archived Plan', 'archived-plan')
            ->price(15, 'USD')
            ->archive();

        $this->assertCount(1, Saas::getAvailablePlans());

        $this->assertEquals('plan', Saas::getAvailablePlans()[0]->getId());
    }

    public function test_build_plans_with_features()
    {
        Saas::clearPlans();

        $plan = Saas::plan('Active Plan', 'plan')
            ->description('Some plan...')
            ->features([
                Saas::feature('Build Minutes', 'build.minutes')
                    ->description('Build minutes for all your projects.')
                    ->value(100),
            ]);

        $this->assertCount(1, $plan->getFeatures());
    }
}
