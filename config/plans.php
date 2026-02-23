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
            'description' => 'Plano gratuito',
            'price' => 0.00,
            'currency' => 'BRL',
            'monthly' => true,
            'links_quota' => 5,
            'logs_quota' => 7,
            'integrations_quota' => 1,
            'stripe_price_id' => null, // set your Stripe price id to enable real billing
            'notifications' => [
                'channels' => [
                    'email' => true,
                    'slack' => false,
                    'discord' => false
                ],
                'events' => [
                    'link_down' => true,
                    'link_error' => true,
                    'link_up' => false,
                    'link_slow' => false
                ],
            ],
        ],

        'starter' => [
            'key' => 'starter',
            'name' => 'Starter',
            'description' => 'Plano Starter',
            'price' => 3.99,
            'currency' => 'BRL',
            'monthly' => true,
            'links_quota' => 25,
            'logs_quota' => 30,
            'integrations_quota' => 3,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER', null),
            'notifications' => [
                'channels' => [
                    'email' => true,
                    'slack' => true,
                    'discord' => true
                ],
                'events' => [
                    'link_down' => true,
                    'link_error' => true,
                    'link_up' => false,
                    'link_slow' => false
                ],
            ],
        ],

        'unlimited' => [
            'key' => 'unlimited',
            'name' => 'Unlimited',
            'description' => 'Plano Ilimitado',
            'price' => 9.99,
            'currency' => 'BRL',
            'monthly' => true,
            'links_quota' => null, // null = unlimited
            'logs_quota' => 365,
            'integrations_quota' => null,
            'stripe_price_id' => env('STRIPE_PRICE_UNLIMITED', null),
            'notifications' => [
                'channels' => [
                    'email' => true,
                    'slack' => true,
                    'discord' => true
                ],
                'events' => [
                    'link_down' => true,
                    'link_error' => true,
                    'link_up' => true,
                    'link_slow' => true
                ],
            ],
        ],
    ],

    'default' => 'free',
];
