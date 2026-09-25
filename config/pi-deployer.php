<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Raspberry Pi Deployer Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file defines default settings for deploying and
    | migrating Laravel applications on Raspberry Pi 5 or local environments.
    |
    */

    'enabled' => env('PI_DEPLOYER_ENABLED', true),

    'route_prefix' => env('PI_DEPLOYER_ROUTE_PREFIX', 'pi-deploy'),

    'middleware' => ['web'],

    'target_path' => env('PI_TARGET_PROJECT_PATH', base_path()),

    'raspberry_pi' => [
        'device_name' => env('PI_DEVICE_NAME', 'Raspberry Pi 5 Pironman (16GB)'),
        'web_user' => env('PI_WEB_USER', 'www-data'),
        'web_group' => env('PI_WEB_GROUP', 'www-data'),
        'default_project_root' => env('PI_PROJECT_ROOT', '/var/www'),
    ],

    'git' => [
        'default_branch' => env('PI_GIT_BRANCH', 'main'),
        'remote' => env('PI_GIT_REMOTE', 'origin'),
    ],

    'composer' => [
        'binary' => env('PI_COMPOSER_BINARY', 'composer'),
        'flags' => '--no-dev --optimize-autoloader --no-interaction',
    ],

    'npm' => [
        'binary' => env('PI_NPM_BINARY', 'npm'),
        'run_build' => env('PI_NPM_BUILD', true),
    ],

    'permissions' => [
        'paths' => [
            'storage',
            'storage/app',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ],
        'chmod' => '0775',
    ],

    'migration' => [
        'force' => true,
        'allow_fresh' => env('PI_ALLOW_FRESH_MIGRATION', false),
    ],

    'seeders' => [
        'default_class' => 'DatabaseSeeder',
    ],
];
