<?php
// Enable error reporting but capture it
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

ob_start();

echo "Binary path: " . __DIR__ . '/../bin/bg-remover' . "\n";
echo "Binary exists: " . (file_exists(__DIR__ . '/../bin/bg-remover') ? 'YES' : 'NO') . "\n";
echo "Binary executable: " . (is_executable(__DIR__ . '/../bin/bg-remover') ? 'YES' : 'NO') . "\n";
echo "\n";
echo "Upload dir: " . __DIR__ . '/uploads' . "\n";
echo "Upload dir exists: " . (is_dir(__DIR__ . '/uploads') ? 'YES' : 'NO') . "\n";
echo "Upload dir writable: " . (is_writable(__DIR__ . '/uploads') ? 'YES' : 'NO') . "\n";
echo "\n";
echo "Output dir: " . __DIR__ . '/outputs' . "\n";
echo "Output dir exists: " . (is_dir(__DIR__ . '/outputs') ? 'YES' : 'NO') . "\n";
echo "Output dir writable: " . (is_writable(__DIR__ . '/outputs') ? 'YES' : 'NO') . "\n";
echo "\n";

// Test binary execution
$binary = __DIR__ . '/../bin/bg-remover';
if (file_exists($binary)) {
    echo "Testing binary execution:\n";
    exec($binary . ' --help 2>&1', $output, $code);
    echo "Exit code: $code\n";
    echo "Output:\n" . implode("\n", $output) . "\n";
}

$content = ob_get_clean();
echo "<pre>$content</pre>";
