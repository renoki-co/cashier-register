<?php

namespace RenokiCo\CashierRegister;

use Illuminate\Contracts\Support\Arrayable;

class Feature implements Arrayable
{
    use Concerns\IsIdentifiable,
        Concerns\HasData;

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
     * Check if this feature is not resettable after each billing cycle.
     *
     * @return bool
     */
    public function isNotResettable(): bool
    {
        return ! $this->isResettable();
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
