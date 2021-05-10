<?php

namespace RenokiCo\CashierRegister\Concerns;

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
     * @param  string|int  $id
     * @param  int  $value
     * @param  bool  $incremental
     * @return \RenokiCo\CashierRegister\Models\Usage|null
     */
    public function recordFeatureUsage($id, int $value = 1, bool $incremental = true)
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
     * @param  string|int  $id
     * @param  int  $uses
     * @return null|\RenokiCo\CashierRegister\Models\Usage
     */
    public function reduceFeatureUsage($id, int $uses = 1, bool $incremental = true)
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
     * @param  string|int  $id
     * @param  int  $uses
     * @return null|\RenokiCo\CashierRegister\Models\Usage
     */
    public function decrementFeatureUsage($id, int $uses = 1, bool $incremental = true)
    {
        return $this->reduceFeatureUsage($id, $uses, $incremental);
    }

    /**
     * Set the feature usage to a specific value.
     *
     * @param  string|int  $id
     * @param  int  $value
     * @return \RenokiCo\CashierRegister\Models\Usage|null
     */
    public function setFeatureUsage($id, int $value)
    {
        return $this->recordFeatureUsage($id, $value, false);
    }

    /**
     * Get the feature used quota.
     *
     * @param  string|int  $id
     * @return int
     */
    public function getUsedQuota($id): int
    {
        $usage = $this->usage()
            ->whereFeatureId($id)
            ->first();

        return $usage ? $usage->used : 0;
    }

    /**
     * Get the feature quota remaining.
     *
     * @param  string|int  $id
     * @param  string|null  $planId
     * @return int
     */
    public function getRemainingQuota($id, $planId = null): int
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
     * @param  string|int  $id
     * @param  string|null  $planId
     * @return int
     */
    public function getFeatureQuota($id, $planId = null): int
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
     * @param  string|int  $id
     * @param  string|null  $planId
     * @return bool
     */
    public function featureOverQuota($id, $planId = null): bool
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

        return $plan->getFeatures()
            ->reject(function ($feature) {
                return $feature->isResettable();
            })
            ->reject(function ($feature) {
                return $feature->isUnlimited();
            })
            ->filter(function (Feature $feature) use ($plan) {
                $remainingQuota = $this->getRemainingQuota(
                    $feature->getId(), $plan->getId()
                );

                return $remainingQuota <= 0;
            });
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
            ->each(function ($usage) use ($plan) {
                $feature = $plan->getFeature($usage->feature_id);

                if ($feature->isResettable()) {
                    $usage->delete();
                }
            });
    }
}
