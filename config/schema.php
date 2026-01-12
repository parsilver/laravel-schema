<?php

// config for Farzai/LaravelSchema

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Schema Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Laravel Schema will be accessible from.
    | If this setting is null, Schema will reside under the same domain
    | as the application.
    |
    */
    'domain' => env('SCHEMA_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Laravel Schema Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Laravel Schema will be accessible from.
    |
    */
    'path' => env('SCHEMA_PATH', 'schema'),

    /*
    |--------------------------------------------------------------------------
    | Laravel Schema Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Laravel Schema route.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Laravel Schema Authorization
    |--------------------------------------------------------------------------
    |
    | This gate determines who can access Laravel Schema in non-local
    | environments. You may customize this gate as needed.
    |
    */
    'authorization' => [
        'enabled' => true,
        'gate' => 'viewLaravelSchema',
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Path
    |--------------------------------------------------------------------------
    |
    | Path to the migrations directory relative to base_path().
    |
    */
    'migrations_path' => 'database/migrations',

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for schema introspection.
    | Set to null to use the default connection.
    |
    */
    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Ignored Tables
    |--------------------------------------------------------------------------
    |
    | Tables to ignore during schema comparison.
    |
    */
    'ignored_tables' => [
        'migrations',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ],
];
