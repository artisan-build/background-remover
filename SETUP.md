# Quick Setup Guide

This guide will help you get started with the background remover package and demo UI.

## What's New

This package now includes:
- Complete C++ source code from [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover)
- Build system (Makefile + Dockerfiles) for compiling binaries
- Interactive demo UI for testing background removal
- Installation script for easy binary setup

## Quick Start (3 Steps)

### 1. Install the Binary

Choose one option:

**Option A: Download Pre-built Binary (Easiest)**
```bash
./install-binary.sh
```

**Option B: Build from Source (If you have OpenCV installed)**
```bash
# macOS
brew install opencv
cd cpp-src && make && mkdir -p ../bin && cp bg-remover ../bin/

# Ubuntu/Debian
sudo apt-get install -y g++ make pkg-config libopencv-dev
cd cpp-src && make && mkdir -p ../bin && cp bg-remover ../bin/
```

**Option C: Build with Docker**
```bash
cd cpp-src
make ubuntu  # or: make alpine
mkdir -p ../bin
cp bg-remover-ubuntu-x86_64 ../bin/bg-remover
chmod +x ../bin/bg-remover
```

### 2. Verify Installation

```bash
./bin/bg-remover --help
```

You should see the help message. If you get an error, see the troubleshooting section below.

### 3. Try the Demo

**If you're using Laravel Herd** (which you are!), just open:
- **http://background-remover.test/demo/**

**Otherwise:**
```bash
cd demo
php -S localhost:8000
```

Then open http://localhost:8000 in your browser and upload an image to test background removal!

## Project Structure

```
background-remover/
├── cpp-src/              # C++ source code & build system
│   ├── bg-remover.cpp    # Main implementation
│   ├── Makefile          # Build configuration
│   └── README.md         # Detailed build instructions
│
├── demo/                 # Interactive web UI
│   ├── index.html        # Frontend interface
│   ├── process.php       # Backend processing
│   └── README.md         # Demo documentation
│
├── src/                  # Laravel package (PHP)
│   ├── Commands/         # Artisan commands
│   ├── Services/         # Background removal service
│   └── Jobs/             # Queue jobs
│
├── bin/                  # Binary installation directory
│   └── bg-remover        # The compiled binary (gitignored)
│
└── install-binary.sh     # Quick binary installer
```

## Usage Modes

### Mode 1: Command Line

```bash
./bin/bg-remover -i input.jpg -o output.png
```

### Mode 2: Demo UI

```bash
cd demo
php -S localhost:8000
# Open http://localhost:8000
```

### Mode 3: Laravel Package

In your Laravel application:

```php
use ArtisanBuild\BackgroundRemover\Services\BackgroundRemovalService;

$service = app(BackgroundRemovalService::class);
$service->removeBackground(
    inputPath: '/path/to/input.jpg',
    outputPath: '/path/to/output.png'
);
```

## How It Works

1. **C++ Binary**: The core background removal algorithm is implemented in C++ using OpenCV's GrabCut algorithm
2. **Laravel Package**: Provides a convenient PHP wrapper for Laravel applications
3. **Demo UI**: A simple web interface for testing and demonstrations

The process:
1. Image is loaded
2. GrabCut algorithm segments foreground from background
3. Morphological operations clean up the mask
4. Gaussian blur smooths edges
5. Alpha channel is added for transparency
6. Result is saved as PNG

## Troubleshooting

### "Binary not found" error

The binary isn't installed. Run:
```bash
./install-binary.sh
```

### "Permission denied" error

Make the binary executable:
```bash
chmod +x bin/bg-remover
```

### Binary crashes or "Abort trap: 6"

The downloaded binary may not be compatible with your system. Build from source:
```bash
# Install OpenCV first
brew install opencv  # macOS

# Then build
cd cpp-src
make
mkdir -p ../bin
cp bg-remover ../bin/
```

### OpenCV not found when building

**macOS:**
```bash
brew install opencv
```

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install -y libopencv-dev
```

**Alpine:**
```bash
apk add opencv opencv-dev
```

### Demo shows "Binary not found"

Make sure the binary is at `bin/bg-remover` relative to the project root:
```bash
ls -la bin/bg-remover
```

If not, install it following step 1 above.

### Poor background removal results

The GrabCut algorithm works best when:
- Subject doesn't touch image edges
- Clear contrast between subject and background
- Subject is centered in the image

Try cropping your image to have more margin around the subject.

## Customizing the Algorithm

To adjust the background removal algorithm, edit `cpp-src/bg-remover.cpp`:

```cpp
// Line ~24: Adjust rectangle inset
Rect rectangle(10, 10, image.cols - 20, image.rows - 20);
// Increase the inset (e.g., 20) for more aggressive edge removal

// Line ~29: Adjust iterations
grabCut(image, mask, rectangle, bgModel, fgModel, 5, GC_INIT_WITH_RECT);
// Increase iterations (e.g., 10) for more accuracy (slower)

// Line ~38: Adjust morphology kernel
Mat kernel = getStructuringElement(MORPH_ELLIPSE, Size(5, 5));
// Increase size (e.g., 7x7) for more aggressive cleanup

// Line ~42: Adjust blur amount
GaussianBlur(mask2, mask2, Size(5, 5), 0);
// Increase size (e.g., 7x7) for smoother edges
```

After editing, rebuild:
```bash
cd cpp-src
make clean
make
cp bg-remover ../bin/
```

## Next Steps

- **For Laravel Development**: See [README.md](README.md) for Laravel package usage
- **For Binary Building**: See [cpp-src/README.md](cpp-src/README.md) for detailed build instructions
- **For Demo Customization**: See [demo/README.md](demo/README.md) for demo documentation

## Resources

- [OpenCV GrabCut Documentation](https://docs.opencv.org/4.x/d8/d83/tutorial_py_grabcut.html)
- [Original bg-remover Repository](https://github.com/artisan-build/bg-remover)
- [OpenCV Installation Guide](https://docs.opencv.org/4.x/df/d65/tutorial_table_of_content_introduction.html)

## Credits

- **C++ Implementation**: [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover)
- **Algorithm**: OpenCV GrabCut (Carsten Rother et al., 2004)
- **Laravel Package**: [Artisan Build](https://artisan.build)

---

Need help? Check the README files in each directory for detailed documentation.
