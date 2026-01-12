<?php
/**
 * Background Remover - Image Processing Endpoint
 *
 * This script handles image uploads and processes them using the bg-remover binary
 */

// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');

// Configuration
define('BINARY_PATH', __DIR__ . '/../bin/bg-remover');
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('OUTPUT_DIR', __DIR__ . '/outputs');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Create directories if they don't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR, 0755, true);
}

// Create .htaccess to allow image access
$htaccessContent = "Options -Indexes\n<FilesMatch \"\.(jpg|jpeg|png|gif)$\">\n    Require all granted\n</FilesMatch>";
if (!file_exists(OUTPUT_DIR . '/.htaccess')) {
    file_put_contents(OUTPUT_DIR . '/.htaccess', $htaccessContent);
}

/**
 * Send JSON response and exit
 */
function sendResponse($success, $data = [], $message = '') {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Send error response
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    sendResponse(false, [], $message);
}

/**
 * Clean up old files (older than 1 hour)
 */
function cleanupOldFiles() {
    $directories = [UPLOAD_DIR, OUTPUT_DIR];
    $maxAge = 3600; // 1 hour

    foreach ($directories as $dir) {
        if (!is_dir($dir)) continue;

        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
                @unlink($file);
            }
        }
    }
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Check if image was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    sendError('No image uploaded or upload error occurred');
}

$file = $_FILES['image'];

// Validate file size
if ($file['size'] > MAX_FILE_SIZE) {
    sendError('File size exceeds maximum allowed size (10MB)');
}

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, ALLOWED_TYPES)) {
    sendError('Invalid file type. Only JPG and PNG images are allowed');
}

// Check if binary exists
if (!file_exists(BINARY_PATH)) {
    sendError('Background remover binary not found. Please run: php artisan background-removal:install', 500);
}

if (!is_executable(BINARY_PATH)) {
    sendError('Binary is not executable. Please run: chmod +x ' . BINARY_PATH, 500);
}

try {
    // Clean up old files
    cleanupOldFiles();

    // Generate unique filenames
    $uniqueId = uniqid('img_', true);
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $inputFilename = $uniqueId . '.' . $extension;
    $outputFilename = $uniqueId . '_output.png';

    $inputPath = UPLOAD_DIR . '/' . $inputFilename;
    $outputPath = OUTPUT_DIR . '/' . $outputFilename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
        throw new Exception('Failed to save uploaded file');
    }

    // Build command (using ML mode with U2-Net model for best results)
    $modelPath = __DIR__ . '/../models/u2net.onnx';
    $command = sprintf(
        '%s -i %s -o %s --model %s 2>&1',
        escapeshellarg(BINARY_PATH),
        escapeshellarg($inputPath),
        escapeshellarg($outputPath),
        escapeshellarg($modelPath)
    );

    // Execute binary
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    // Check if processing was successful
    if ($returnCode !== 0 || !file_exists($outputPath)) {
        $errorMessage = implode("\n", $output);
        throw new Exception('Background removal failed: ' . $errorMessage);
    }

    // Get file size for info
    $outputSize = filesize($outputPath);

    // Generate public URL for the output image
    $outputUrl = 'outputs/' . $outputFilename;

    // Clean up input file (optional - comment out if you want to keep it)
    @unlink($inputPath);

    // Send success response
    sendResponse(true, [
        'outputUrl' => $outputUrl,
        'filename' => $outputFilename,
        'size' => $outputSize,
        'sizeFormatted' => formatBytes($outputSize)
    ], 'Background removed successfully');

} catch (Exception $e) {
    // Clean up files on error
    if (isset($inputPath) && file_exists($inputPath)) {
        @unlink($inputPath);
    }
    if (isset($outputPath) && file_exists($outputPath)) {
        @unlink($outputPath);
    }

    sendError($e->getMessage(), 500);
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
