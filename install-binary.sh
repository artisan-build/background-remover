#!/bin/bash

# Background Remover Binary Installation Script
# This script downloads the appropriate binary for your platform

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Background Remover Binary Installer${NC}"
echo "======================================"
echo ""

# Detect platform
OS=$(uname -s)
ARCH=$(uname -m)

echo "Detected OS: $OS"
echo "Detected Architecture: $ARCH"
echo ""

# Determine binary name
BINARY_NAME=""

case "$OS" in
    Darwin)
        if [ "$ARCH" = "arm64" ]; then
            BINARY_NAME="bg-remover-macos-arm64"
            echo -e "Platform: ${GREEN}macOS Apple Silicon${NC}"
        else
            echo -e "${YELLOW}Warning: macOS Intel (x86_64) binary not available${NC}"
            echo "You'll need to build from source or use Rosetta 2"
            exit 1
        fi
        ;;
    Linux)
        if [ -f /etc/alpine-release ]; then
            BINARY_NAME="bg-remover-alpine-x86_64"
            echo -e "Platform: ${GREEN}Alpine Linux${NC}"
        elif [ -f /etc/debian_version ] || [ -f /etc/ubuntu-version ]; then
            BINARY_NAME="bg-remover-ubuntu-x86_64"
            echo -e "Platform: ${GREEN}Ubuntu/Debian${NC}"
        else
            BINARY_NAME="bg-remover-ubuntu-x86_64"
            echo -e "Platform: ${YELLOW}Linux (trying Ubuntu binary)${NC}"
        fi
        ;;
    *)
        echo -e "${RED}Error: Unsupported operating system: $OS${NC}"
        echo "Please build from source. See cpp-src/README.md"
        exit 1
        ;;
esac

# GitHub release URL
REPO="artisan-build/bg-remover"
RELEASE_URL="https://github.com/${REPO}/releases/latest/download/${BINARY_NAME}"

echo ""
echo "Downloading binary..."
echo "URL: $RELEASE_URL"
echo ""

# Create bin directory
mkdir -p bin

# Download binary
if command -v curl &> /dev/null; then
    curl -L -o "bin/bg-remover" "$RELEASE_URL"
elif command -v wget &> /dev/null; then
    wget -O "bin/bg-remover" "$RELEASE_URL"
else
    echo -e "${RED}Error: Neither curl nor wget is available${NC}"
    exit 1
fi

# Check if download was successful
if [ ! -f "bin/bg-remover" ] || [ ! -s "bin/bg-remover" ]; then
    echo -e "${RED}Error: Failed to download binary${NC}"
    exit 1
fi

# Make executable
chmod +x bin/bg-remover

echo ""
echo -e "${GREEN}✅ Binary installed successfully!${NC}"
echo ""
echo "Installation location: $(pwd)/bin/bg-remover"
echo ""

# Verify installation
if bin/bg-remover --help &> /dev/null; then
    echo -e "${GREEN}✅ Binary is working correctly${NC}"
else
    echo -e "${YELLOW}⚠️  Binary downloaded but might not be compatible with your system${NC}"
    echo "Try building from source instead. See cpp-src/README.md"
fi

echo ""
echo "Next steps:"
echo "1. Test the binary: ./bin/bg-remover -i input.jpg -o output.png"
echo "2. Run the demo: cd demo && php -S localhost:8000"
echo ""
