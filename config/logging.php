<?php
$env = env('APP_ENV');
if ($env == 'development') $env = 'dev';
if ($env == 'production') $env = 'prod';
if ($env == 'staging') $env = 'stag';

return [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['rollbar', 'single'],
    ],
    'channels' => [
        'rollbar' => [
            'driver' => 'monolog',
            'handler' => \Rollbar\Laravel\MonologHandler::class,
            'access_token' => env('ROLLBAR_TOKEN'),
            'level' => 'error',
            'environment' => $env . '_' . env('APP_NAME'),
        ]
    ]
];
