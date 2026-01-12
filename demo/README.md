# Background Remover Demo UI

A simple web interface to test the background removal functionality.

## Quick Start

### 1. Download ML Models

The demo uses ML-based background removal for best results. Download the U2-Net ONNX model:

```bash
cd ..
mkdir -p models
curl -L -o models/u2net.onnx https://github.com/danielgatis/rembg/releases/download/v0.0.0/u2net.onnx
```

Alternative model (RMBG-1.4):
```bash
curl -L -o models/rmbg-1.4.onnx https://huggingface.co/briaai/RMBG-1.4/resolve/main/onnx/model.onnx
```

### 2. Install the Binary

You have two options to get the binary:

#### Option A: Download Pre-built Binary (Recommended)

For macOS (Apple Silicon):
```bash
cd ..
mkdir -p bin
curl -L -o bin/bg-remover https://github.com/artisan-build/bg-remover/releases/latest/download/bg-remover-macos-arm64
chmod +x bin/bg-remover
```

For Ubuntu/Debian:
```bash
cd ..
mkdir -p bin
curl -L -o bin/bg-remover https://github.com/artisan-build/bg-remover/releases/latest/download/bg-remover-ubuntu-x86_64
chmod +x bin/bg-remover
```

For Alpine Linux:
```bash
cd ..
mkdir -p bin
curl -L -o bin/bg-remover https://github.com/artisan-build/bg-remover/releases/latest/download/bg-remover-alpine-x86_64
chmod +x bin/bg-remover
```

#### Option B: Build from Source

Requirements:
- g++ compiler
- OpenCV 4.x
- make
- pkg-config

**On macOS:**
```bash
# Install OpenCV via Homebrew
brew install opencv

# Build the binary
cd ../cpp-src
make
mkdir -p ../bin
cp bg-remover ../bin/
```

**On Ubuntu/Debian:**
```bash
# Install dependencies
sudo apt-get update
sudo apt-get install -y g++ make pkg-config libopencv-dev

# Build the binary
cd ../cpp-src
make
mkdir -p ../bin
cp bg-remover ../bin/
```

**Using Docker (for cross-platform builds):**
```bash
cd ../cpp-src

# For Ubuntu binary
make ubuntu

# For Alpine binary
make alpine

# Copy to bin directory
mkdir -p ../bin
cp bg-remover-* ../bin/bg-remover
chmod +x ../bin/bg-remover
```

### 3. Start the Demo

You need a web server to run the demo. Here are your options:

#### Option A: Laravel Herd (Recommended if you have it)
If you're using Laravel Herd and this project is in your `~/Herd` directory, the demo is already running!

Just open: **http://background-remover.test/demo/**

#### Option B: PHP Built-in Server
```bash
php -S localhost:8000
```

Then open your browser to: http://localhost:8000

#### Option C: Any Web Server
Point your web server (Apache, Nginx, etc.) to the `demo` directory.

### 4. Test It Out

1. Open the demo in your browser
2. Click or drag-and-drop an image
3. Wait a few seconds for ML processing
4. Download the result with the transparent background

## Directory Structure

```
demo/
├── index.html         # Main UI
├── process.php        # Backend processing endpoint
├── uploads/           # Temporary uploaded images (auto-created)
├── outputs/           # Processed images (auto-created)
└── README.md          # This file
```

## Features

- **ML-powered** background removal using U2-Net ONNX model
- Drag-and-drop image upload
- Real-time preview
- Side-by-side comparison
- Automatic cleanup of old files (1 hour)
- Support for JPG and PNG images
- Maximum file size: 10MB
- Download processed images

## Troubleshooting

### "Binary not found" error

Make sure you've installed the binary:
```bash
ls -la ../bin/bg-remover
```

If it doesn't exist, follow step 1 above.

### "Permission denied" error

Make the binary executable:
```bash
chmod +x ../bin/bg-remover
```

### PHP errors

Make sure PHP has write permissions for the demo directory:
```bash
chmod -R 755 .
```

### Processing takes too long

Large images may take 5-10 seconds. The GrabCut algorithm is computationally intensive.

### Poor background removal quality

The algorithm works best with:
- Clear subject/background separation
- Subjects that don't touch the image edges
- Good contrast between subject and background

For better results, you may need to:
- Crop the image to have more margin around the subject
- Adjust the rectangle parameter in the C++ code
- Try different images

## How It Works

1. User uploads an image via the web interface
2. PHP receives the image and saves it temporarily
3. PHP executes the `bg-remover` binary with the U2-Net ONNX model
4. The binary uses ML-based segmentation (via ONNX Runtime) to remove the background
5. Result is saved as a PNG with an alpha channel
6. UI displays the result and allows downloading

## ML Mode vs GrabCut

The demo uses **ML mode** by default with the U2-Net model for superior results:
- Better edge detection
- Handles complex backgrounds
- More accurate subject segmentation

To use traditional **GrabCut** instead, edit `process.php` and replace:
```php
--model models/u2net.onnx
```
with:
```php
--grabcut -q quality
```

## Credits

- C++ Binary: [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover)
- Algorithm: OpenCV GrabCut
- Package: [artisan-build/background-remover](https://github.com/artisan-build/background-remover)
