<?php

namespace RenokiCo\CashierRegister\Concerns;

trait HasPlans
{
   /**
     * Get the plan this subscription belongs to.
     *
     * @return \RenokiCo\CashierRegister\Plan
     */
    public function getPlan()
    {
        return Saas::getPlan($this->stripe_plan);
    }
}
