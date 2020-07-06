<?php

namespace RenokiCo\Fuel\Models;

use Laravel\Cashier\Subscription as CashierSubscription;
use RenokiCo\Fuel\Exceptions\FeatureUsageOverflowException;
use RenokiCo\Fuel\Saas;

class Subscription extends CashierSubscription
{
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
     * Get the plan this subscription belongs to.
     *
     * @return \RenokiCo\Fuel\Plan
     */
    public function getPlan()
    {
        return Saas::getPlan($this->stripe_plan);
    }

    /**
     * Increment the feature usage.
     *
     * @param  string  $id
     * @param  int  $value
     * @param  bool  $incremental
     * @return \RenokiCo\Fuel\Models\Usage|null
     * @throws \RenokiCo\Fuel\Exceptions\FeatureUsageOverflowException
     */
    public function recordFeatureUsage(string $id, int $value = 1, bool $incremental = true)
    {
        $feature = $this->getPlan()
            ->getFeature($id);

        if (! $feature) {
            return;
        }

        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->getId(),
        ]);

        if ($feature->isResettable()) {
            $usage->fill([
                'valid_until' => is_null($usage->ends_at)
                    ? $feature->getResetDate($this->created_at)
                    : $feature->getResetDate($this->ends_at),
            ]);
        }

        $newValue = $incremental ? $usage->used + $value : $value;

        if ($feature->getValue() > 0 && $newValue > $feature->getValue()) {
            throw new FeatureUsageOverflowException('The feature usage is beyond the limit of the feature value.');
        }

        $usage->fill([
            'used' => $newValue,
        ]);

        return tap($usage)->save();
    }

    /**
     * Reduce the usage amount.
     *
     * @param  string  $id
     * @param  int  $uses
     * @return null|\RenokiCo\Fuel\Models\Usage
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
     * Get how many times the feature has been used.
     *
     * @param  string  $id
     * @return int
     */
    public function getFeatureUsage(string $id): int
    {
        $usage = $this->usage()
            ->whereFeatureId($id)
            ->first();

        if (! $usage) {
            return 0;
        }

        return $usage->expired() ? 0 : $usage->used;
    }

    /**
     * Get the available uses.
     *
     * @param  string  $id
     * @return int
     */
    public function getFeatureRemainings(string $id): int
    {
        $featureValue = $this->getFeatureValue($id);

        if ($featureValue < 0) {
            return -1;
        }

        return $featureValue - $this->getFeatureUsage($id);
    }

    /**
     * Get feature value.
     *
     * @param  string  $id
     * @return int
     */
    public function getFeatureValue(string $id): int
    {
        $feature = $this->getPlan()
            ->getFeature($id);

        if (! $feature) {
            return 0;
        }

        return $feature->getValue();
    }
}
