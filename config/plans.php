<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application subscription plans
    |--------------------------------------------------------------------------
    | Used by the pricing page, quota checks and (optionally) billing
    | integrations. Add real Stripe price IDs in `stripe_price_id` to enable
    | live subscriptions with Laravel Cashier.
    |
    */

    'plans' => [
        'free' => [
            'key' => 'free',
            'name' => 'Free',
            'price' => 0.00,
            'currency' => 'BRL',
            'monthly' => true,
            'links_quota' => 5,
            'logs_quota' => 7,
            'stripe_price_id' => null, // set your Stripe price id to enable real billing
        ],

        'starter' => [
            'key' => 'starter',
            'name' => 'Starter',
            'price' => 3.99,
            'currency' => 'BRL',
            'monthly' => true,
            'links_quota' => 25,
            'logs_quota' => 30,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER', null),
        ],

        'unlimited' => [
            'key' => 'unlimited',
            'name' => 'Unlimited',
            'price' => 9.90,
            'currency' => 'BRL',
            'monthly' => true,
            'links_quota' => null, // null = unlimited
            'logs_quota' => 365,
            'stripe_price_id' => env('STRIPE_PRICE_UNLIMITED', null),
        ],
    ],

    'default' => 'free',
];
