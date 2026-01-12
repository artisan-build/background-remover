# Background Remover for Laravel

[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A Laravel wrapper for the [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover) C++ binary, providing fast and efficient background removal using OpenCV's GrabCut algorithm and optional ML-based segmentation.

## Features

- 🚀 **Fast** - Native C++ performance using OpenCV
- 🤖 **ML Support** - Optional ONNX Runtime for ML-based segmentation (U2-Net, RMBG)
- 🎯 **Simple API** - Easy-to-use Laravel service class
- ☁️ **Cloud Storage** - Built-in support for Laravel Storage (S3, local, etc.)
- 📦 **Multi-platform** - Supports Ubuntu (Forge) and macOS ARM64
- 🔒 **Checksum Verification** - Ensures binary integrity
- ⚡ **Queue Support** - Async processing via Laravel queues
- 🔧 **Build from Source** - Includes full C++ source code and build system
- 🎨 **Demo UI** - Interactive web interface for testing

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
- **Ubuntu/Debian** → `bg-remover-ubuntu-x86_64` (for Laravel Forge, Vapor, etc.)
- **macOS** → `bg-remover-macos-arm64` (for Apple Silicon development)

### Manual Platform Selection

If auto-detection fails or you need a specific platform:

```bash
php artisan background-removal:install --platform=ubuntu
php artisan background-removal:install --platform=macos-arm64
```

### Custom Platform Support

The package only maintains binaries for platforms we actively use. For other platforms (Windows, Raspberry Pi, Arch, etc.), see the [FORKING.md](https://github.com/artisan-build/bg-remover/blob/main/FORKING.md) guide to build your own binary.

## Quick Install Binary (Standalone)

If you're not using Laravel or want to quickly install the binary:

```bash
# Run the installation script
./install-binary.sh
```

This will auto-detect your platform and download the appropriate binary to `bin/bg-remover`.

## Building from Source

This package now includes the complete C++ source code. You can build the binary yourself:

### Quick Build (macOS)

```bash
# Install OpenCV and ONNX Runtime
brew install opencv onnxruntime

# Build with ML support
cd cpp-src
make ML=1

# Or build without ML support
make

# Install
mkdir -p ../bin
cp bg-remover ../bin/
```

### Quick Build (Ubuntu/Debian)

```bash
# Install dependencies
sudo apt-get update
sudo apt-get install -y g++ make pkg-config libopencv-dev

# Optional: Install ONNX Runtime for ML support
# Follow: https://github.com/microsoft/onnxruntime/releases

# Build with ML support (if ONNX Runtime installed)
cd cpp-src
make ML=1

# Or build without ML support
make

# Install
mkdir -p ../bin
cp bg-remover ../bin/
```

### Docker Builds (Cross-platform)

```bash
cd cpp-src

# Build for Ubuntu (includes ML support)
make ubuntu

# Install
mkdir -p ../bin
cp bg-remover-ubuntu-x86_64 ../bin/bg-remover
chmod +x ../bin/bg-remover
```

For detailed build instructions, see [cpp-src/README.md](cpp-src/README.md).

## Demo UI

Try out the background remover with a simple web interface:

1. Install the binary (see above)
2. Start the demo:
   ```bash
   cd demo
   php -S localhost:8000
   ```
3. Open http://localhost:8000 in your browser
4. Upload an image and see the result

The demo features:
- Drag-and-drop image upload
- Side-by-side comparison
- Download processed images
- Support for JPG and PNG

For more details, see [demo/README.md](demo/README.md).

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

The Ubuntu binary works on AWS Lambda environments (including Vapor) and includes full ML support.

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

### Ubuntu (Laravel Forge, Vapor, etc.)
- Uses system OpenCV libraries
- Includes ONNX Runtime for ML support
- Standard glibc-based binary
- ~23KB binary size

### macOS ARM64 (Development)
- Native Apple Silicon binary
- Uses Homebrew OpenCV and ONNX Runtime
- Full ML support included
- Universal x86_64 support coming soon

## ML Mode

All binaries are compiled with ML support via ONNX Runtime. To use ML-based segmentation:

```bash
# Download an ONNX model (e.g., U2-Net, RMBG-1.4)
# Then use with --ml flag
bg-remover -i input.jpg -o output.png --ml --model path/to/model.onnx
```

ML mode provides superior edge detection and works better with complex backgrounds compared to the traditional GrabCut algorithm.

## Project Structure

```
background-remover/
├── src/                      # Laravel package source
│   ├── Commands/             # Artisan commands
│   ├── Jobs/                 # Queue jobs
│   ├── Providers/            # Service providers
│   └── Services/             # Background removal service
├── cpp-src/                  # C++ source code
│   ├── bg-remover.cpp        # Main C++ implementation
│   ├── Makefile              # Build system with ML support
│   ├── Dockerfile.ubuntu     # Ubuntu build with ONNX Runtime
│   └── README.md             # Build documentation
├── demo/                     # Interactive demo UI
│   ├── index.html            # Web interface
│   ├── process.php           # Processing endpoint
│   └── README.md             # Demo documentation
├── config/                   # Laravel config
├── tests/                    # Package tests
├── bin/                      # Binary installation directory
├── install-binary.sh         # Binary installation script
└── README.md                 # This file
```

## Troubleshooting

### Binary Not Found

```bash
# For Laravel projects
php artisan background-removal:install

# For standalone usage
./install-binary.sh

# Or build from source
cd cpp-src && make && mkdir -p ../bin && cp bg-remover ../bin/
```

### Permission Denied

```bash
# Make binary executable
chmod +x bin/bg-remover
```

### Platform Not Supported

Build from source using the included C++ code:
```bash
cd cpp-src
# Follow instructions in cpp-src/README.md
```

Or see [FORKING.md](https://github.com/artisan-build/bg-remover/blob/main/FORKING.md) for building custom platform binaries.

## Credits

- **Binary**: [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover)
- **Algorithm**: OpenCV GrabCut
- **Maintained by**: [Artisan Build](https://artisan.build)

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.

## Related Projects

- [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover) - The C++ binary
- [artisan-build/scalpels](https://github.com/artisan-build/scalpels) - Collection of Laravel utilities
