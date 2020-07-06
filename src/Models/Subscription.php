<?php

namespace RenokiCo\LaravelSaas\Models;

use Illuminate\Database\Eloquent\Model;
use RenokiCo\LaravelSaas\Exceptions\FeatureUsageOverflowException;
use RenokiCo\LaravelSaas\Plan;
use RenokiCo\LaravelSaas\Saas;

class Subscription extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'saas_subscriptions';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'user_id', 'user_type', 'plan_id',
        'name', 'description',
        'trial_ends_at', 'starts_at', 'ends_at',
        'cancels_at', 'canceled_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'trial_ends_at', 'starts_at', 'ends_at',
        'cancels_at', 'canceled_at',
    ];

    /**
     * Get the model this subscription belongs to.
     *
     * @return mixed
     */
    public function model()
    {
        return $this->morphTo();
    }

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
     * Return only active subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            return $query->where('trial_ends_at', '>', now())
                ->where('starts_at', '>', now())
                ->where(function ($query) {
                    return $query->where('cancels_at', '>', now())
                        ->orWhere('cancels_at', null);
                });
        })
        ->orWhere(function ($query) {
            return $query->where('starts_at', '<', now())
                ->where('ends_at', '>', now())
                ->where(function ($query) {
                    return $query->where('cancels_at', '>', now())
                        ->orWhere('cancels_at', null);
                });
        });
    }

    /**
     * Get the plan this subscription belongs to.
     *
     * @return \RenokiCo\LaravelSaas\Plan
     */
    public function getPlan()
    {
        return Saas::getPlan($this->plan_id);
    }

    /**
     * Check if subscription is still active.
     *
     * @return bool
     */
    public function active(): bool
    {
        return ! $this->ended() || $this->onTrial();
    }

    /**
     * Check if subscription is currently on trial.
     *
     * @return bool
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at
            ? now()->lt($this->trial_ends_at)
            : false;
    }

    /**
     * Check if subscription is canceled.
     *
     * @return bool
     */
    public function canceled(): bool
    {
        return $this->canceled_at
            ? now()->gte($this->canceled_at)
            : false;
    }

    /**
     * Check if subscription period has ended.
     *
     * @return bool
     */
    public function ended(): bool
    {
        return $this->ends_at
            ? now()->gte($this->ends_at)
            : false;
    }

    /**
     * Cancel subscription.
     *
     * @param  bool  $immediately
     * @return $this
     */
    public function cancel(bool $immediately = false)
    {
        $this->fill([
            'canceled_at' => now(),
            'cancels_at' => $immediately ? now() : $this->ends_at,
            'ends_at' => $immediately ? now() : $this->ends_at,
            'trial_ends_at' => $immediately ? now() : $this->trial_ends_at,
        ]);

        $subscription = tap($this)->save();

        sleep(1);

        return $subscription;
    }

    /**
     * Change subscription plan.
     *
     * @param  \RenokiCo\LaravelSaas\Plan  $plan
     * @return $this
     */
    public function changePlan(Plan $plan)
    {
        $this->usage()->delete();

        $this->setNewPeriod(
            $plan->getInvoiceInterval(), $plan->getInvoicePeriod()
        )->fill([
            'plan_id' => $plan->getId(),
        ]);

        $subscription = tap($this)->save();

        sleep(1);

        return $subscription;
    }

    /**
     * Renew the subscription period.
     *
     * @return $this
     */
    public function renew()
    {
        if ($this->ended()) {
            return $this;
        }

        $this->usage()->delete();

        $this->setNewPeriod();

        $this->fill([
            'canceled_at' => null,
            'cancels_at' => null,
        ]);

        $subscription = tap($this)->save();

        sleep(1);

        return $subscription;
    }

    /**
     * Set new subscription period.
     *
     * @param  string|\Carbon\Carbon  $invoiceInterval
     * @param  int  $invoicePeriod
     * @return $this
     */
    protected function setNewPeriod($invoiceInterval = null, $invoicePeriod = null)
    {
        $plan = $this->getPlan();

        $invoiceInterval = $invoiceInterval ?: $plan->getInvoiceInterval();
        $invoicePeriod = $invoicePeriod ?: $plan->getInvoicePeriod();

        $start = now();

        return $this->fill([
            'starts_at' => $start,
            'ends_at' => $start->copy()->add($invoicePeriod, $invoiceInterval),
        ]);
    }

    /**
     * Increment the feature usage.
     *
     * @param  string  $id
     * @param  int  $value
     * @param  bool  $incremental
     * @return \RenokiCo\LaravelSaas\Models\Usage|null
     * @throws \RenokiCo\LaravelSaas\Exceptions\FeatureUsageOverflowException
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
                'valid_until' => is_null($usage->valid_until)
                    ? $feature->getResetDate($this->created_at)
                    : $feature->getResetDate($this->valid_until),
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
     * @return null|\RenokiCo\LaravelSaas\Models\Usage
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
