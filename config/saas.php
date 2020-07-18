<?php

return [

    'models' => [

        'usage' => \RenokiCo\CashierRegister\Models\Usage::class,

    ],

    'cashier' => [

        'models' => [

            'subscription' => [

                'stripe' => \RenokiCo\CashierRegister\Models\StripeSubscription::class,

                'paddle' => \RenokiCo\CashierRegister\Models\PaddleSubscription::class,
            ]
        ],
    ],

];
