<?php

namespace RenokiCo\Fuel;

use Illuminate\Contracts\Support\{ Arrayable };

class Plan implements Arrayable
{
    /**
     * The display name of the plan.
     *
     * @var string
     */
    protected $name;

    /**
     * An unique id for the plan.
     *
     * @var string
     */
    protected $id;

    /**
     * Description for the plan.
     *
     * @var string
     */
    protected $description;

    /**
     * The plan price.
     *
     * @var float
     */
    protected $price = 0.00;

    /**
     * The currency of the price.
     *
     * @var string
     */
    protected $currency = 'EUR';

    /**
     * The trial period. Works with $trialInterval.
     * For example, this can be 10 if $trialInterval
     * is 'day'.
     *
     * @var int
     */
    protected $trialPeriod = 0;

    /**
     * The interval for the trial.
     * It can be month, day, etc. This should be
     * supported by Carbon\Carbon.
     *
     * @var string
     */
    protected $trialInterval = 'month';

    /**
     * The trial period. Works with $invoiceInterval.
     * For example, this can be 10 if $invoiceInterval
     * is 'day'.
     *
     * @var int
     */
    protected $invoicePeriod = 1;

    /**
     * The interval for the plan basic invoicing.
     * It can be month, day, etc. This should be
     * supported by Carbon\Carbon.
     *
     * @var string
     */
    protected $invoiceInterval = 'month';

    /**
     * The grace period. Works with $graceInterval.
     * For example, this can be 10 if $graceInterval
     * is 'day'.
     *
     * @var int
     */
    protected $gracePeriod = 3;

    /**
     * The interval for the grace period.
     * It can be month, day, etc. This should be
     * supported by Carbon\Carbon.
     *
     * @var string
     */
    protected $graceInterval = 'day';

    /**
     * Wether the plan is active.
     *
     * @var bool
     */
    protected $active = true;

    /**
     * The features list for the plan.
     *
     * @var array|\Illuminate\Support\Collection
     */
    protected $features = [];

    /**
     * Create a new plan builder.
     *
     * @param  string  $name
     * @param  string  $id
     * @return void
     */
    public function __construct(string $name, string $id)
    {
        $this->name = $name;
        $this->id = $id;
        $this->features = collect([]);
    }

    /**
     * Set a description for the plan.
     *
     * @param  string  $description
     * @return $this
     */
    public function description(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Assign a price.
     *
     * @param  float  $price
     * @param  string  $currency
     * @return $this
     */
    public function price(float $price, string $currency = 'EUR')
    {
        $this->price = $price;
        $this->currency = $currency;

        return $this;
    }

    /**
     * Set the trial for the plan.
     *
     * @param  int  $period
     * @param  string  $interval
     * @return $this
     */
    public function trial($period = 0, $interval = 'day')
    {
        $this->trialPeriod = $period;
        $this->trialInterval = $interval;

        return $this;
    }

    /**
     * Set the invoice for the plan.
     *
     * @param  int  $period
     * @param  string  $interval
     * @return $this
     */
    public function invoice($period = 0, $interval = 'day')
    {
        $this->invoicePeriod = $period;
        $this->invoiceInterval = $interval;

        return $this;
    }

    /**
     * Invoice monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->invoice(1, 'month');
    }

    /**
     * Set the grace period for the plan.
     *
     * @param  int  $period
     * @param  string  $interval
     * @return $this
     */
    public function grace($period = 0, $interval = 'day')
    {
        $this->gracePeriod = $period;
        $this->graceInterval = $interval;

        return $this;
    }

    /**
     * Deprecate the plan.
     *
     * @return $this
     */
    public function archive()
    {
        $this->active = false;

        return $this;
    }

    /**
     * Attach features to the plan.
     *
     * @param  array  $features
     * @return $this
     */
    public function features(array $features)
    {
        $this->features = collect($features)
            ->filter(function (Feature $feature) {
                return $feature instanceof Feature;
            })
            ->unique(function (Feature $feature) {
                return $feature->getId();
            });

        return $this;
    }

    /**
     * Get the id of the plan.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Check if the plan is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Get the trial period.
     *
     * @return int
     */
    public function getTrialPeriod(): int
    {
        return $this->trialPeriod;
    }

    /**
     * Get the trial interval.
     *
     * @return string
     */
    public function getTrialInterval(): string
    {
        return $this->trialInterval;
    }

    /**
     * Get the invoice period.
     *
     * @return int
     */
    public function getInvoicePeriod(): int
    {
        return $this->invoicePeriod;
    }

    /**
     * Get the invoice interval.
     *
     * @return string
     */
    public function getInvoiceInterval(): string
    {
        return $this->invoiceInterval;
    }

    /**
     * Get the list of all features.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFeatures()
    {
        return collect($this->features);
    }

    /**
     * Get a specific feature by id.
     *
     * @param  string  $id
     * @return Feature|null
     */
    public function getFeature(string $id)
    {
        return $this->getFeatures()->filter(function (Feature $feature) use ($id) {
            return $feature->getId() === $id;
        })->first();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'trial_period' => $this->trialPeriod,
            'trial_interval' => $this->trialInterval,
            'invoice_period' => $this->invoicePeriod,
            'invoice_interval' => $this->invoiceInterval,
            'grace_period' => $this->gracePeriod,
            'active' => $this->active,
            'features' => $this->getFeatures()->toArray(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
