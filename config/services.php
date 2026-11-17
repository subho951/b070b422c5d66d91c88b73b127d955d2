<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'minimum_score' => (float) env('RECAPTCHA_MINIMUM_SCORE', 0.5),
        'hostnames' => array_filter(array_map('trim', explode(',', env('RECAPTCHA_HOSTNAMES', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')))),
    ],

    'google_reviews' => [
        'enabled' => env('GOOGLE_REVIEWS_ENABLED', false),
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
        'place_id' => env('GOOGLE_REVIEWS_PLACE_ID'),
        'profile_url' => 'https://www.google.com/search?q=Howvanta+Technologies&stick=H4sIAAAAAAAA_-NgU1I1qDBONDAytzRPSko0MbJMMUyzMqgwTDUwMTYwMkoyMzMwN08xWcQq6pFfXpaYV5KoEJKanJGXn5OfnplaDABw9VVAQQAAAA&hl=en-GB',
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
