<?php

namespace RenokiCo\CashierRegister\Contracts;

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
     * @return $this
     */
    public function data(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the list of all features.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getData()
    {
        return $this->data;
    }
}
