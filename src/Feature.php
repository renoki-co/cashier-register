<?php

namespace RenokiCo\CashierRegister;

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
     * Mark the feature as being resettable.
     *
     * @var bool
     */
    protected $resettable = true;

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
     * Mark the feature as not resettable.
     *
     * @return $this
     */
    public function notResettable()
    {
        $this->resettable = false;

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
     * Get the name of the feature.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the description of the feature.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
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
        return $this->resettable;
    }

    /**
     * Check if the feature has unlimited uses.
     *
     * @return bool
     */
    public function isUnlimited(): bool
    {
        return $this->getValue() < 0;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'value' => $this->getValue(),
            'unlimited' => $this->isUnlimited(),
            'resettable' => $this->isResettable(),
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
