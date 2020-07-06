<?php

namespace RenokiCo\Fuel\Traits;

use RenokiCo\Fuel\Exceptions\PlanArchivedException;
use RenokiCo\Fuel\Plan;

trait HasSubscriptions
{
    /**
     * The subscriptions of this model.
     *
     * @return mixed
     */
    public function saasSubscriptions()
    {
        return $this->morphMany(config('saas.models.subscription'), 'model');
    }

    /**
     * Filter only the active subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeSaasSubscriptions()
    {
        return $this->saasSubscriptions()
            ->active();
    }

    /**
     * Subscribe user to a new plan.
     *
     * @param  string  $subscription
     * @param  \RenokiCo\Fuel\Plan  $plan
     * @return \RenokiCo\Fuel\Subscription
     * @throws \RenokiCo\Fuel\Exceptions\PlanArchivedException
     */
    public function newSaasSubscription($subscription, Plan $plan)
    {
        if (! $plan->isActive()) {
            throw new PlanArchivedException('The plan is archived and cannot be used anymore.');
        }

        $trialEnd = $this->hadTrialFor($plan)
            ? now()
            : now()->add($plan->getTrialPeriod(), $plan->getTrialInterval());

        $start = $trialEnd->copy();

        $end = $start->copy()
            ->add($plan->getInvoicePeriod(), $plan->getInvoiceInterval());

        $subscription = $this->saasSubscriptions()->create([
            'name' => $subscription,
            'plan_id' => $plan->getId(),
            'trial_ends_at' => $trialEnd,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        sleep(1);

        return $subscription;
    }

    /**
     * Check if the model subscribed to the given plan.
     *
     * @param  \RenokiCo\Fuel\Plan  $plan
     * @return bool
     */
    public function subscribedToSaasPlan(Plan $plan): bool
    {
        return $this->activeSaasSubscriptions()
            ->wherePlanId($plan->getId())
            ->exists();
    }

    /**
     * Check if the user had a trial so far.
     *
     * @param  \RenokiCo\Fuel\Plan  $plan
     * @return bool
     */
    public function hadTrialFor(Plan $plan): bool
    {
        return $this->saasSubscriptions()
            ->wherePlanId($plan->getId())
            ->count() >= 1;
    }

    /**
     * Get the active subscription by name.
     *
     * @param  string  $name
     * @return \RenokiCo\Fuel\Subscription|null
     */
    public function activeSaasSubscription(string $name)
    {
        return $this->activeSaasSubscriptions()
            ->whereName($name)
            ->first();
    }
}
