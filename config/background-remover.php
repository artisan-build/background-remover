<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the bg-remover binary. By default, it will look in the
    | package's bin directory. You can override this to use a custom path.
    |
    */

    'binary_path' => env('BG_REMOVER_BINARY_PATH', base_path('bin/bg-remover')),

    /*
    |--------------------------------------------------------------------------
    | GitHub Release Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for downloading binaries from GitHub releases.
    |
    */

    'github' => [
        'repo' => 'artisan-build/bg-remover',
        'version' => 'latest', // or specific version like 'v1.0.0'
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Detection
    |--------------------------------------------------------------------------
    |
    | Automatic platform detection for binary downloads.
    | Supported platforms: alpine, ubuntu, macos-arm64
    |
    */

    'platform' => env('BG_REMOVER_PLATFORM', null), // null = auto-detect

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum execution time for the binary in seconds.
    |
    */

    'timeout' => env('BG_REMOVER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    |
    | Directory for temporary files during processing.
    | Uses sys_get_temp_dir() by default.
    |
    */

    'temp_dir' => env('BG_REMOVER_TEMP_DIR', sys_get_temp_dir()),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for queue-based background removal jobs.
    |
    */

    'queue' => [
        'connection' => env('BG_REMOVER_QUEUE_CONNECTION', null),
        'queue' => env('BG_REMOVER_QUEUE', 'default'),
    ],
];
