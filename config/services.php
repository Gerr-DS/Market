<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'dprice' => [
        'url' => env('DPRICE_API_URL', 'https://dprice.id/api/v1'),
        'key' => env('DPRICE_API_KEY'),
        'enabled' => env('DPRICE_ENABLED', false),
    ],
    'payment' => ['webhook_secret' => env('PAYMENT_WEBHOOK_SECRET')],
    'logistics' => [
        'webhook_secret' => env('LOGISTICS_WEBHOOK_SECRET'),
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'api_url' => env('RAJAONGKIR_API_URL', 'https://rajaongkir.komerce.id/api/v1'),
        'couriers' => env('RAJAONGKIR_COURIERS', 'jne:sicepat:ide:sap:jnt:ninja:tiki:lion:anteraja:pos:ncs:rex:rpx:sentral:star:wahana'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
