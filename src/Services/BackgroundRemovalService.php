<?php

declare(strict_types=1);

namespace ArtisanBuild\BackgroundRemover\Services;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BackgroundRemovalService
{
    public function __construct(
        private readonly string $binaryPath,
        private readonly int $timeout,
        private readonly string $tempDir
    ) {}

    public function removeBackground(string $inputPath, string $outputPath): void
    {
        if (! file_exists($this->binaryPath)) {
            throw new RuntimeException(
                "Binary not found at {$this->binaryPath}. Run 'php artisan background-removal:install' first."
            );
        }

        if (! is_executable($this->binaryPath)) {
            throw new RuntimeException(
                "Binary at {$this->binaryPath} is not executable. Check permissions."
            );
        }

        $result = Process::timeout($this->timeout)->run([
            $this->binaryPath,
            '-i',
            $inputPath,
            '-o',
            $outputPath,
        ]);

        if (! $result->successful()) {
            throw new ProcessFailedException($result);
        }
    }

    public function removeBackgroundFromStorage(
        string $inputDisk,
        string $inputPath,
        string $outputDisk,
        string $outputPath
    ): void {
        $tempInputPath = $this->createTempPath('input');
        $tempOutputPath = $this->createTempPath('output.png');

        try {
            Storage::disk($inputDisk)->get($inputPath);
            file_put_contents($tempInputPath, Storage::disk($inputDisk)->get($inputPath));

            $this->removeBackground($tempInputPath, $tempOutputPath);

            Storage::disk($outputDisk)->put($outputPath, file_get_contents($tempOutputPath));
        } finally {
            @unlink($tempInputPath);
            @unlink($tempOutputPath);
        }
    }

    private function createTempPath(string $suffix): string
    {
        $tempFile = tempnam($this->tempDir, 'bg_remover_');
        if ($tempFile === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        $tempPath = $tempFile . '_' . $suffix;
        rename($tempFile, $tempPath);

        return $tempPath;
    }
}
