<?php

namespace RenokiCo\CashierRegister\Concerns;

trait HasData
{
    /**
     * The data list for the instance.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Attach data to the instance.
     *
     * @param  array  $data
     * @return self
     */
    public function data(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the plan as being popular.
     *
     * @return self
     */
    public function popular()
    {
        return $this->data(array_merge(
            $this->getData(), ['popular' => true]
        ));
    }

    /**
     * Get the list of all features.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
