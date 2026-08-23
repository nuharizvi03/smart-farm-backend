<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN'
            ),
            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenWeather
    |--------------------------------------------------------------------------
    */

    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY'),

        'base_url' => env(
            'OPENWEATHER_BASE_URL',
            'https://api.openweathermap.org'
        ),

        'cache_minutes' => (int) env(
            'OPENWEATHER_CACHE_MINUTES',
            30
        ),

        // Standard Current Weather API
        'weather_endpoint' => env(
            'OPENWEATHER_WEATHER_ENDPOINT',
            '/data/2.5/weather'
        ),

        // 5-day / 3-hour forecast API
        'forecast_endpoint' => env(
            'OPENWEATHER_FORECAST_ENDPOINT',
            '/data/2.5/forecast'
        ),

        // Geocoding API
        'geocoding_endpoint' => env(
            'OPENWEATHER_GEOCODING_ENDPOINT',
            '/geo/1.0/direct'
        ),
    ],

];