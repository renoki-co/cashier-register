<?php

namespace RenokiCo\CashierRegister\Concerns;

use Closure;
use RenokiCo\CashierRegister\Feature;
use RenokiCo\CashierRegister\MeteredFeature;
use RenokiCo\CashierRegister\Models\Usage;
use RenokiCo\CashierRegister\Plan;
use RenokiCo\CashierRegister\Saas;

trait HasQuotas
{
    use HasPlans;

    /**
     * Get the feature usages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usage()
    {
        return $this->hasMany(config('saas.models.usage'));
    }

    /**
     * Increment the feature usage.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  int|float  $value
     * @param  bool  $incremental
     * @param  Closure|null  $exceedHandler
     * @return \RenokiCo\CashierRegister\Models\Usage|null
     */
    public function recordFeatureUsage($feature, $value = 1, bool $incremental = true, Closure $exceedHandler = null)
    {
        $plan = $this->getPlan();
        $feature = $plan->getFeature($feature);

        if (! $feature) {
            return;
        }

        /** @var \RenokiCo\CashierRegister\Models\Usage $usage */
        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature,
        ]);

        // Try to recalculate the usage based on user-defined callbacks.
        $usage->recalculate($this, $feature);

        $usage->fill([
            'used' => $incremental ? $usage->used + $value : $value,
            'used_total' => $incremental ? $usage->used_total + $value : $value,
        ]);

        $featureOverQuota = $this->featureOverQuotaFor($feature, $usage, $plan);

        if (! $feature->isUnlimited() && $featureOverQuota) {
            $remainingQuota = $this->getRemainingQuotaFor($feature, $usage, $plan);

            $valueOverQuota = value(function () use ($value, $remainingQuota) {
                return $remainingQuota < 0
                    ? -1 * $remainingQuota
                    : $value;
            });

            if ($feature instanceof MeteredFeature && method_exists($this, 'reportUsageFor')) {
                /** @var MeteredFeature $feature */
                /** @var \Laravel\Cashier\Subscription $this */

                // If the user has for example 5 minutes left and the pipeline
                // ended and 10 minutes were consumed, update the feature usage to
                // feature value (meaning everything got consumed) and report
                // the usage based on the difference for the remaining difference,
                // but with positive value.
                $this->reportUsageFor($feature->getMeteredId(), $valueOverQuota);
            }

            /** @var Feature $feature */

            // Fill the usage later since the getRemainingQuotaFor() uses the $usage
            // object that was updated with the current requested feature usage recording.
            // This way, the next time the customer uses again the feature, it will jump straight up
            // to billing using metering instead of calculating the difference.
            $usage->fill([
                'used' => $this->getFeatureQuota($feature, $plan),
                'used_total' => $incremental ? $usage->used_total + $value : $value,
            ]);

            if ($exceedHandler) {
                $exceedHandler($feature, $valueOverQuota, $this);
            }
        }

        return tap($usage)->save();
    }

    /**
     * Reduce the usage amount.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $id
     * @param  int|float  $uses
     * @return null|\RenokiCo\CashierRegister\Models\Usage
     */
    public function reduceFeatureUsage($feature, $uses = 1, bool $incremental = true)
    {
        /** @var \RenokiCo\CashierRegister\Models\Usage|null $usage */
        $feature = $this->getPlan()->getFeature($feature);

        $usage = $this->usage()
            ->whereFeatureId($feature)
            ->first();

        if (is_null($usage)) {
            return;
        }

        // Try to recalculate the usage based on user-defined callbacks.
        $usage->recalculate($this, $feature);

        $used = max($incremental ? $usage->used - $uses : $uses, 0);

        $usage->fill([
            'used' => $used,
            'used_total' => $used,
        ]);

        return tap($usage)->save();
    }

    /**
     * Reduce the usage amount.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  int|float  $uses
     * @return null|\RenokiCo\CashierRegister\Models\Usage
     */
    public function decrementFeatureUsage($feature, $uses = 1, bool $incremental = true)
    {
        return $this->reduceFeatureUsage($feature, $uses, $incremental);
    }

    /**
     * Set the feature usage to a specific value.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  int|float  $value
     * @return \RenokiCo\CashierRegister\Models\Usage|null
     */
    public function setFeatureUsage($feature, $value)
    {
        return $this->recordFeatureUsage($feature, $value, false);
    }

    /**
     * Get the feature used quota.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @return int|float
     */
    public function getUsedQuota($feature)
    {
        /** @var \RenokiCo\CashierRegister\Models\Usage|null $usage */
        $usage = $this->usage()
            ->whereFeatureId($feature)
            ->first();

        return $usage ? $usage->used : 0;
    }

    /**
     * Get the feature quota remaining.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  \RenokiCo\CashierRegister\Plan|string|int|null  $plan
     * @return int|float
     */
    public function getRemainingQuota($feature, $plan)
    {
        $featureValue = $this->getFeatureQuota($feature, $plan);

        if ($featureValue < 0) {
            return -1;
        }

        return $featureValue - $this->getUsedQuota($feature);
    }

    /**
     * Get the feature quota remaining.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  \RenokiCo\CashierRegister\Models\Usage  $usage
     * @param  \RenokiCo\CashierRegister\Plan|string|int|null  $plan
     * @return int|float
     */
    public function getRemainingQuotaFor($feature, $usage, $plan = null)
    {
        $featureValue = $this->getFeatureQuota($feature, $plan);

        if ($featureValue < 0) {
            return -1;
        }

        return $featureValue - $usage->used;
    }

    /**
     * Get the feature quota.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  \RenokiCo\CashierRegister\Plan|string|int|null  $plan
     * @return int|float
     */
    public function getFeatureQuota($feature, $plan = null)
    {
        $plan = $plan ? Saas::getPlan($plan) : $this->getPlan();

        $feature = $plan->getFeature($feature);

        if (! $feature) {
            return 0;
        }

        return $feature->getValue();
    }

    /**
     * Check if the feature is over the assigned quota.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  \RenokiCo\CashierRegister\Plan|string|int|null  $plan
     * @return bool
     */
    public function featureOverQuota($feature, $plan = null): bool
    {
        $plan = $plan ? Saas::getPlan($plan) : $this->getPlan();

        $feature = $plan->getFeature($feature);

        if ($feature->isUnlimited()) {
            return false;
        }

        return $this->getRemainingQuota($feature, $plan) < 0;
    }

    /**
     * Check if the feature is over the assigned quota.
     *
     * @param  \RenokiCo\CashierRegister\Feature|string|int  $feature
     * @param  \RenokiCo\CashierRegister\Models\Usage  $usage
     * @param  \RenokiCo\CashierRegister\Plan|string|int|null  $plan
     * @return bool
     */
    public function featureOverQuotaFor($feature, $usage, $plan = null): bool
    {
        $plan = $plan ? Saas::getPlan($plan) : $this->getPlan();

        $feature = $plan->getFeature($feature);

        if ($feature->isUnlimited()) {
            return false;
        }

        return $this->getRemainingQuotaFor($feature, $usage, $plan) < 0;
    }

    /**
     * Check if there are features over quota
     * if the current subscription would be swapped
     * to a new one.
     *
     * @param  \RenokiCo\CashierRegister\Plan|string|int|null  $plan
     * @return \Illuminate\Support\Collection
     */
    public function featuresOverQuotaWhenSwapping($plan)
    {
        $plan = Saas::getPlan($plan);

        return $plan->getFeatures()
            ->reject->isResettable()
            ->reject->isUnlimited()
            ->filter(function (Feature $feature) use ($plan) {
                $remainingQuota = $this->getRemainingQuota($feature, $plan);

                return $remainingQuota < 0;
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
            ->each(function (Usage $usage) use ($plan) {
                $feature = $plan->getFeature($usage->feature_id);

                if ($feature->isResettable()) {
                    $usage->delete();
                }
            });
    }
}
