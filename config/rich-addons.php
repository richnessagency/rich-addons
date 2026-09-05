<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Local Add-ons Directory
    |--------------------------------------------------------------------------
    |
    | Path where locally-installed add-ons reside. Each add-on lives in its
    | own subdirectory and must contain an addon.json manifest file.
    |
    */
    'addons_path' => env('RICH_ADDONS_PATH', base_path('addons')),

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, the kernel will automatically scan the addons_path and
    | Composer's installed packages for add-on manifests on every boot.
    |
    */
    'auto_discover' => (bool) env('RICH_ADDONS_AUTO_DISCOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Admin Route Configuration
    |--------------------------------------------------------------------------
    */
    'admin_route_prefix' => env('RICH_ADDONS_ROUTE_PREFIX', 'admin/addons'),
    'admin_middleware' => ['web', 'auth', 'admin'],

    /*
    |--------------------------------------------------------------------------
    | Licensing (Future — Paid & Subscription Add-ons)
    |--------------------------------------------------------------------------
    |
    | These settings are reserved for the licensing subsystem. They have no
    | effect on free add-ons but the schema is defined here so that paid
    | add-on support can be enabled without config changes.
    |
    */
    'license_server_url' => env('RICH_ADDONS_LICENSE_SERVER', ''),
    'public_key_path' => env('RICH_ADDONS_PUBLIC_KEY', ''),
    'heartbeat_interval_hours' => (int) env('RICH_ADDONS_HEARTBEAT_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache the discovered add-on registry. Set to
    | 0 to disable caching (useful during development).
    |
    */
    'cache_seconds' => (int) env('RICH_ADDONS_CACHE_SECONDS', 0),
];
