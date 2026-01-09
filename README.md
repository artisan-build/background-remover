# Background Remover for Laravel

[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A Laravel wrapper for the [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover) C++ binary, providing fast and efficient background removal using OpenCV's GrabCut algorithm.

## Features

- 🚀 **Fast** - Native C++ performance using OpenCV
- 🎯 **Simple API** - Easy-to-use Laravel service class
- ☁️ **Cloud Storage** - Built-in support for Laravel Storage (S3, local, etc.)
- 📦 **Multi-platform** - Supports Alpine (Vapor), Ubuntu (Forge), and macOS ARM64
- 🔒 **Checksum Verification** - Ensures binary integrity
- ⚡ **Queue Support** - Async processing via Laravel queues

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+

## Installation

Install the package via Composer:

```bash
composer require artisan-build/background-remover
```

Install the binary for your platform:

```bash
php artisan background-removal:install
```

### Platform Auto-Detection

The install command automatically detects your platform:
- **Alpine Linux** → `bg-remover-alpine-x86_64` (for Laravel Vapor)
- **Ubuntu/Debian** → `bg-remover-ubuntu-x86_64` (for Laravel Forge)
- **macOS** → `bg-remover-macos-arm64` (for Apple Silicon development)

### Manual Platform Selection

If auto-detection fails or you need a specific platform:

```bash
php artisan background-removal:install --platform=alpine
php artisan background-removal:install --platform=ubuntu
php artisan background-removal:install --platform=macos-arm64
```

### Custom Platform Support

The package only maintains binaries for platforms we actively use. For other platforms (Windows, Raspberry Pi, Arch, etc.), see the [FORKING.md](https://github.com/artisan-build/bg-remover/blob/main/FORKING.md) guide to build your own binary.

## Usage

### Basic Usage

```php
use ArtisanBuild\BackgroundRemover\Services\BackgroundRemovalService;

$service = app(BackgroundRemovalService::class);

// Remove background from local files
$service->removeBackground(
    inputPath: '/path/to/input.jpg',
    outputPath: '/path/to/output.png'
);
```

### Using Laravel Storage

```php
use ArtisanBuild\BackgroundRemover\Services\BackgroundRemovalService;

$service = app(BackgroundRemovalService::class);

// Remove background using Storage disks
$service->removeBackgroundFromStorage(
    inputDisk: 's3',
    inputPath: 'uploads/photo.jpg',
    outputDisk: 's3',
    outputPath: 'processed/photo-no-bg.png'
);
```

### Using Queues (Recommended for Production)

```php
use ArtisanBuild\BackgroundRemover\Jobs\RemoveBackgroundJob;

// Dispatch to queue
RemoveBackgroundJob::dispatch(
    inputDisk: 's3',
    inputPath: 'uploads/photo.jpg',
    outputDisk: 's3',
    outputPath: 'processed/photo-no-bg.png'
);

// Dispatch to specific queue
RemoveBackgroundJob::dispatch(...)
    ->onQueue('image-processing');

// Dispatch to specific connection
RemoveBackgroundJob::dispatch(...)
    ->onConnection('redis');
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=background-remover-config
```

This creates `config/background-remover.php`:

```php
return [
    // Path to the binary (auto-installed to base_path('bin/bg-remover'))
    'binary_path' => env('BG_REMOVER_BINARY_PATH', base_path('bin/bg-remover')),

    // GitHub release settings
    'github' => [
        'repo' => 'artisan-build/bg-remover',
        'version' => 'latest', // or specific version like 'v1.0.0'
    ],

    // Platform (null = auto-detect)
    'platform' => env('BG_REMOVER_PLATFORM', null),

    // Timeout in seconds
    'timeout' => env('BG_REMOVER_TIMEOUT', 60),

    // Temporary directory for processing
    'temp_dir' => env('BG_REMOVER_TEMP_DIR', sys_get_temp_dir()),

    // Queue configuration
    'queue' => [
        'connection' => env('BG_REMOVER_QUEUE_CONNECTION', null),
        'queue' => env('BG_REMOVER_QUEUE', 'default'),
    ],
];
```

### Environment Variables

```env
# Custom binary path
BG_REMOVER_BINARY_PATH=/usr/local/bin/bg-remover

# Force specific platform
BG_REMOVER_PLATFORM=alpine

# Timeout (seconds)
BG_REMOVER_TIMEOUT=120

# Queue settings
BG_REMOVER_QUEUE_CONNECTION=redis
BG_REMOVER_QUEUE=image-processing
```

## Laravel Vapor Deployment

The binary is automatically downloaded during build. No additional configuration needed!

```bash
# Vapor will automatically run:
php artisan background-removal:install
```

The install command detects the Alpine environment and downloads the correct binary.

## Laravel Forge Deployment

Add to your deployment script:

```bash
php artisan background-removal:install
```

The binary is installed to `base_path('bin/bg-remover')` and committed (or installed on each deploy).

## Production Best Practices

### 1. Use Queues

Always process images asynchronously in production:

```php
RemoveBackgroundJob::dispatch(...)
    ->onQueue('image-processing');
```

### 2. Configure Timeout

For large images, increase the timeout:

```env
BG_REMOVER_TIMEOUT=120
```

### 3. Monitor Storage

The service downloads files to temporary storage for processing. Ensure adequate disk space:

```php
// Default uses sys_get_temp_dir()
// Or customize:
BG_REMOVER_TEMP_DIR=/path/to/large/temp/dir
```

### 4. Error Handling

Wrap processing in try-catch:

```php
use Illuminate\Process\Exceptions\ProcessFailedException;

try {
    $service->removeBackgroundFromStorage(...);
} catch (ProcessFailedException $e) {
    Log::error('Background removal failed', [
        'error' => $e->getMessage(),
        'input' => $inputPath,
    ]);
}
```

## Testing

```bash
# Run package tests
composer test

# Run with coverage
composer test:coverage
```

## How It Works

1. **Download**: Images are downloaded from Laravel Storage to temporary files
2. **Process**: The C++ binary processes the image using OpenCV's GrabCut algorithm
3. **Upload**: Processed images are uploaded back to Laravel Storage
4. **Cleanup**: Temporary files are automatically deleted

## Platform Notes

### Alpine Linux (Laravel Vapor)
- Uses statically-linked OpenCV
- Optimized for AWS Lambda
- ~23KB binary size

### Ubuntu (Laravel Forge)
- Uses system OpenCV libraries
- Standard glibc-based binary
- ~23KB binary size

### macOS ARM64 (Development)
- Native Apple Silicon binary
- Uses Homebrew OpenCV
- Universal x86_64 support coming soon

## Troubleshooting

### Binary Not Found

```bash
# Reinstall the binary
php artisan background-removal:install
```

### Permission Denied

```bash
# Make binary executable
chmod +x bin/bg-remover
```

### Platform Not Supported

See [FORKING.md](https://github.com/artisan-build/bg-remover/blob/main/FORKING.md) for building custom platform binaries.

## Credits

- **Binary**: [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover)
- **Algorithm**: OpenCV GrabCut
- **Maintained by**: [Artisan Build](https://artisan.build)

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.

## Related Projects

- [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover) - The C++ binary
- [artisan-build/scalpels](https://github.com/artisan-build/scalpels) - Collection of Laravel utilities
