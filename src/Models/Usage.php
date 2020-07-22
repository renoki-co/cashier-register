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
}
