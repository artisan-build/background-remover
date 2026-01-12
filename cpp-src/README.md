# Background Remover - C++ Source Code

This directory contains the C++ source code for the background remover binary, which uses OpenCV's GrabCut algorithm to remove image backgrounds.

## Source Files

- `bg-remover.cpp` - Main C++ implementation
- `Makefile` - Build system for local and Docker builds
- `Dockerfile.alpine` - Alpine Linux build container
- `Dockerfile.ubuntu` - Ubuntu build container

## Building from Source

### Prerequisites

- C++ compiler (g++ or clang++)
- OpenCV 4.x
- make
- pkg-config

### macOS

#### Install Dependencies
```bash
brew install opencv
```

#### Build
```bash
make
```

This creates the `bg-remover` binary in the current directory.

#### Install to bin directory
```bash
mkdir -p ../bin
cp bg-remover ../bin/
chmod +x ../bin/bg-remover
```

### Ubuntu/Debian

#### Install Dependencies
```bash
sudo apt-get update
sudo apt-get install -y g++ make pkg-config libopencv-dev
```

#### Build
```bash
make
```

#### Install to bin directory
```bash
mkdir -p ../bin
cp bg-remover ../bin/
chmod +x ../bin/bg-remover
```

### Alpine Linux

#### Install Dependencies
```bash
apk add --no-cache g++ make pkgconfig opencv opencv-dev
```

#### Build
```bash
make
```

#### Install to bin directory
```bash
mkdir -p ../bin
cp bg-remover ../bin/
chmod +x ../bin/bg-remover
```

## Building with Docker

Docker builds allow you to create binaries for different platforms without installing dependencies locally.

### Build for Ubuntu
```bash
make ubuntu
```

This creates `bg-remover-ubuntu-x86_64` in the current directory.

### Build for Alpine
```bash
make alpine
```

This creates `bg-remover-alpine-x86_64` in the current directory.

### Install Docker-built binary
```bash
mkdir -p ../bin
cp bg-remover-ubuntu-x86_64 ../bin/bg-remover
# or
cp bg-remover-alpine-x86_64 ../bin/bg-remover
chmod +x ../bin/bg-remover
```

## Build Targets

- `make` or `make all` - Build for local system
- `make alpine` - Build Alpine Linux binary using Docker
- `make ubuntu` - Build Ubuntu binary using Docker
- `make clean` - Remove all built binaries

## Usage

Once built, you can use the binary:

```bash
./bg-remover -i input.jpg -o output.png
```

### Options

- `-i, --input` - Input image file path (required)
- `-o, --output` - Output image file path (required)
- `-h, --help` - Show help message

## How It Works

The binary uses OpenCV's GrabCut algorithm for intelligent foreground/background segmentation:

1. **Image Loading** - Reads the input image
2. **GrabCut Algorithm** - Segments foreground from background using a rectangular region
3. **Binary Mask Creation** - Creates a mask distinguishing foreground (255) from background (0)
4. **Morphological Operations** - Cleans up the mask using closing and opening operations
5. **Edge Smoothing** - Applies Gaussian blur for smooth transitions
6. **Alpha Channel** - Adds transparency to create a PNG with a transparent background
7. **Output** - Saves the result with maximum PNG compression

## Performance

- Typical processing time: 1-5 seconds for standard photos
- Memory usage: ~2-3x the input image size
- Recommended max image size: 4096x4096 pixels

## Algorithm Details

**GrabCut Parameters:**
- Rectangle inset: 10 pixels from each edge
- Iterations: 5
- Mode: `GC_INIT_WITH_RECT`

**Morphological Operations:**
- Kernel: 5x5 ellipse
- Operations: CLOSE followed by OPEN

**Edge Smoothing:**
- Gaussian blur: 5x5 kernel

## Customization

To adjust the algorithm parameters, edit `bg-remover.cpp`:

```cpp
// Adjust rectangle inset (currently 10 pixels)
Rect rectangle(10, 10, image.cols - 20, image.rows - 20);

// Adjust iterations (currently 5)
grabCut(image, mask, rectangle, bgModel, fgModel, 5, GC_INIT_WITH_RECT);

// Adjust kernel size for morphology (currently 5x5)
Mat kernel = getStructuringElement(MORPH_ELLIPSE, Size(5, 5));

// Adjust blur size (currently 5x5)
GaussianBlur(mask2, mask2, Size(5, 5), 0);
```

After making changes, rebuild with `make clean && make`.

## Troubleshooting

### "opencv2/opencv.hpp: No such file or directory"

OpenCV is not installed or not in the include path. Install OpenCV for your platform.

### "Package opencv4 was not found"

The pkg-config file for OpenCV is missing. Try:
```bash
# macOS
brew reinstall opencv

# Ubuntu
sudo apt-get install --reinstall libopencv-dev
```

### "undefined reference to cv::..."

OpenCV libraries are not being linked. Check that pkg-config is working:
```bash
pkg-config --cflags --libs opencv4
```

### Binary crashes on execution

The binary may be built for a different platform or architecture. Rebuild from source on your target platform.

## License

MIT License - See LICENSE.md in the root directory.

## Credits

- Original repository: [artisan-build/bg-remover](https://github.com/artisan-build/bg-remover)
- Algorithm: OpenCV GrabCut (Carsten Rother et al., 2004)
- Package maintained by [Artisan Build](https://artisan.build)
