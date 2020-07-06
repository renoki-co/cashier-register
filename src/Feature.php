<?php

namespace RenokiCo\LaravelSaas;

use Carbon\Carbon;
use Illuminate\Contracts\Support\{ Arrayable };

class Feature implements Arrayable
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
     * The value of this feature. Used
     * to track down the usability
     * of this specific feature
     * at the subscription level.
     *
     * @var int
     */
    protected $value = 0;

    /**
     * The trial period. Works with $invoiceInterval.
     * For example, this can be 10 if $invoiceInterval
     * is 'day'.
     *
     * @var int
     */
    protected $resetPeriod = 1;

    /**
     * The interval for the plan basic invoicing.
     * It can be month, day, etc. This should be
     * supported by Carbon\Carbon.
     *
     * @var string
     */
    protected $resetInterval = 'month';

    /**
     * Create a new feature builder.
     *
     * @param  string  $name
     * @param  string  $id
     * @param  int  $value
     * @return void
     */
    public function __construct(string $name, string $id, int $value = 0)
    {
        $this->name = $name;
        $this->id = $id;
        $this->value = $value;
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
     * Set a new value for the usability.
     *
     * @param  int  $value
     * @return $this
     */
    public function value(int $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set the feature as unlimited value.
     *
     * @param  int  $value
     * @return $this
     */
    public function unlimited()
    {
        $this->value = -1;

        return $this;
    }

    /**
     * Set the reset interval for the usability
     * of this feature.
     *
     * @param  int  $period
     * @param  string  $interval
     * @return $this
     */
    public function reset($period = 0, $interval = 'day')
    {
        $this->resetPeriod = $period;
        $this->resetInterval = $interval;

        return $this;
    }

    /**
     * Mark the feature as not resettable.
     *
     * @return $this
     */
    public function notResettable()
    {
        $this->resetPeriod = 0;
        $this->resetInterval = 'day';

        return $this;
    }

    /**
     * Get the id of the feature.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the feature value.
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Check if this feature is resettable after each billing cycle.
     *
     * @return bool
     */
    public function isResettable(): bool
    {
        return $this->resetPeriod && $this->resetInterval;
    }

    /**
     * Get feature's reset date.
     *
     * @param  string|\Carbon\Carbon|null  $from
     * @return \Carbon\Carbon
     */
    public function getResetDate($from = null): Carbon
    {
        return Carbon::parse($from)
            ->add($this->resetPeriod, $this->resetInterval);
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
            'value' => $this->value,
            'reset_period' => $this->resetPeriod,
            'reset_interval' => $this->resetInterval,
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
