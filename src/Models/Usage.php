<?php

namespace RenokiCo\CashierRegister\Models;

use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'subscription_usages';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'subscription_id', 'feature_id',
        'used',
    ];

    /**
     * The feature this usage belongs to.
     *
     * @return mixed
     */
    public function feature()
    {
        return $this->belongsTo(config('saas.models.feature'));
    }

    /**
     * The subscription this usage is for.
     *
     * @return mixed
     */
    public function subscription()
    {
        return $this->belongsTo(config('saas.cashier.models.subscription'));
    }
}
