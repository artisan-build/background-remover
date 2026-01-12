<?php
header('Content-Type: application/json');

$binary = __DIR__ . '/../bin/bg-remover';

$result = [
    'binary_path' => $binary,
    'binary_exists' => file_exists($binary),
    'binary_executable' => is_executable($binary),
    'binary_realpath' => realpath($binary),
    'current_dir' => __DIR__,
    'uploads_writable' => is_writable(__DIR__ . '/uploads') || !file_exists(__DIR__ . '/uploads'),
    'outputs_writable' => is_writable(__DIR__ . '/outputs') || !file_exists(__DIR__ . '/outputs'),
];

// Try to execute the binary
if (file_exists($binary) && is_executable($binary)) {
    exec($binary . ' --help 2>&1', $output, $return_code);
    $result['binary_test'] = [
        'return_code' => $return_code,
        'output' => implode("\n", $output)
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
