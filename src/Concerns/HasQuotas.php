<?php

namespace RenokiCo\CashierRegister\Concerns;

use RenokiCo\CashierRegister\Models\Usage;
use RenokiCo\CashierRegister\Feature;
use RenokiCo\CashierRegister\Saas;

trait HasQuotas
{
    use HasPlans;

    /**
     * Get the feature usages.
     *
     * @return mixed
     */
    public function usage()
    {
        return $this->hasMany(config('saas.models.usage'));
    }

    /**
     * Increment the feature usage.
     *
     * @param  string  $id
     * @param  int  $value
     * @param  bool  $incremental
     * @return \RenokiCo\CashierRegister\Models\Usage|null
     */
    public function recordFeatureUsage(string $id, int $value = 1, bool $incremental = true)
    {
        $feature = $this->getPlan()->getFeature($id);

        if (! $feature) {
            return;
        }

        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->getId(),
        ]);

        $usage->fill([
            'used' => $incremental ? $usage->used + $value : $value,
        ]);

        return tap($usage)->save();
    }

    /**
     * Reduce the usage amount.
     *
     * @param  string  $id
     * @param  int  $uses
     * @return null|\RenokiCo\CashierRegister\Models\Usage
     */
    public function reduceFeatureUsage(string $id, int $uses = 1, bool $incremental = true)
    {
        $usage = $this->usage()
            ->whereFeatureId($id)
            ->first();

        if (is_null($usage)) {
            return;
        }

        $usage->fill([
            'used' => max(
                $incremental ? $usage->used - $uses : $uses,
                0
            ),
        ]);

        return tap($usage)->save();
    }

    /**
     * Reduce the usage amount.
     *
     * @param  string  $id
     * @param  int  $uses
     * @return null|\RenokiCo\CashierRegister\Models\Usage
     */
    public function decrementFeatureUsage(string $id, int $uses = 1, bool $incremental = true)
    {
        return $this->reduceFeatureUsage($id, $uses, $incremental);
    }

    /**
     * Set the feature usage to a specific value.
     *
     * @param  string  $id
     * @param  int  $value
     * @return \RenokiCo\CashierRegister\Models\Usage|null
     */
    public function setFeatureUsage(string $id, int $value)
    {
        return $this->recordFeatureUsage($id, $value, false);
    }

    /**
     * Get the feature used quota.
     *
     * @param  string  $id
     * @return int
     */
    public function getUsedQuota(string $id): int
    {
        $usage = $this->usage()
            ->whereFeatureId($id)
            ->first();

        return $usage ? $usage->used : 0;
    }

    /**
     * Get the feature quota remaining.
     *
     * @param  string  $id
     * @param  null|string  $planId
     * @return int
     */
    public function getRemainingQuota(string $id, $planId = null): int
    {
        $featureValue = $this->getFeatureQuota($id, $planId);

        if ($featureValue < 0) {
            return -1;
        }

        return $featureValue - $this->getUsedQuota($id);
    }

    /**
     * Get the feature quota.
     *
     * @param  string  $id
     * @param  null|string  $planId
     * @return int
     */
    public function getFeatureQuota(string $id, $planId = null): int
    {
        $plan = $planId ? Saas::getPlan($planId) : $this->getPlan();

        $feature = $plan->getFeature($id);

        if (! $feature) {
            return 0;
        }

        return $feature->getValue();
    }

    /**
     * Check if the feature is over the assigned quota.
     *
     * @param  string  $id
     * @param  null|string  $planId
     * @return bool
     */
    public function featureOverQuota(string $id, $planId = null): bool
    {
        $plan = $planId ? Saas::getPlan($planId) : $this->getPlan();

        $feature = $plan->getFeature($id);

        if ($feature->isUnlimited()) {
            return false;
        }

        return $this->getRemainingQuota($id, $planId) < 0;
    }

    /**
     * Check if there are features over quota
     * if the current subscription would be swapped
     * to a new one.
     *
     * @param  string  $planId
     * @return \Illuminate\Support\Collection
     */
    public function featuresOverQuotaWhenSwapping(string $planId)
    {
        $plan = Saas::getPlan($planId);

        return collect($plan->getFeatures())
            ->filter
            ->isNotResettable()
            ->filter(function (Feature $feature) use ($plan) {
                return $this->getRemainingQuota(
                    $feature->getId(), $plan->getId()
                ) < 0;
            })
            ->values();
    }

    /**
     * Reset the quotas of this subscription.
     *
     * @return void
     */
    public function resetQuotas()
    {
        $plan = $this->getPlan();

        $this->usage()
            ->get()
            ->each(function (Usage $usage) use ($plan) {
                $feature = $plan->getFeature($usage->feature_id);

                if ($feature->isResettable()) {
                    $usage->delete();
                }
            });
    }
}
