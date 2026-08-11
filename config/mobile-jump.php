<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | The URI prefix for all Mobile Jump routes. Change this if it conflicts
    | with existing routes in your application.
    |
    | API routes  → /{prefix}/api/*
    | Dashboard   → /{prefix}/dashboard
    | Artisan cmd → php artisan mobile:jump
    */
    'route_prefix' => env('MOBILE_JUMP_PREFIX', 'mobile-jump'),

    /*
    |--------------------------------------------------------------------------
    | Session TTL (seconds)
    |--------------------------------------------------------------------------
    | How long a QR session remains valid after generation.
    | Default: 600 seconds (10 minutes).
    */
    'session_ttl' => (int) env('MOBILE_JUMP_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Session Storage Driver
    |--------------------------------------------------------------------------
    | Where session data is persisted.
    |
    | Supported: "redis", "database", "file"
    |
    | "redis"    — fastest; requires the predis/predis or phpredis extension.
    | "database" — stores in the mobile_jump_sessions table (run the migration).
    | "file"     — zero-dependency fallback; stores JSON in storage/framework/mobile-jump/.
    */
    'storage' => env('MOBILE_JUMP_STORAGE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    | The named Redis connection from config/database.php to use when driver is "redis".
    */
    'redis_connection' => env('MOBILE_JUMP_REDIS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Register Routes
    |--------------------------------------------------------------------------
    | Set to false if you want to register Mobile Jump routes manually.
    */
    'register_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | API Middleware
    |--------------------------------------------------------------------------
    | Middleware applied to the API endpoints.
    */
    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | APK Download URL
    |--------------------------------------------------------------------------
    | Override to serve the APK from a CDN or custom location.
    | null = the package uses the bundled APK published to public/vendor/mobile-jump/.
    */
    'apk_url' => env('MOBILE_JUMP_APK_URL', null),

];
