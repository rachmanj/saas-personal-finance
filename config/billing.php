<?php

return [

    'stripe_price_pro' => env('STRIPE_PRICE_PRO'),

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'features' => [
                'Up to 2 accounts',
                'Manual transactions',
                'Basic reports',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 9.99,
            'features' => [
                'Unlimited accounts',
                'OCR & voice input',
                'AI categorization',
                'Budgets & recurring',
                'Export & Google Sheets',
            ],
        ],
    ],

];
