<?php

declare(strict_types=1);

namespace ArtisanBuild\BackgroundRemover\Tests\Unit;

use ArtisanBuild\BackgroundRemover\Services\BackgroundRemovalService;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BackgroundRemovalServiceTest extends TestCase
{
    private BackgroundRemovalService $service;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir();
        $this->service = new BackgroundRemovalService(
            binaryPath: '/usr/local/bin/bg-remover',
            timeout: 60,
            tempDir: $this->tempDir
        );
    }

    public function test_throws_exception_when_binary_not_found(): void
    {
        $service = new BackgroundRemovalService(
            binaryPath: '/nonexistent/path',
            timeout: 60,
            tempDir: $this->tempDir
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Binary not found');

        $service->removeBackground('/input.jpg', '/output.png');
    }

    public function test_throws_exception_when_binary_not_executable(): void
    {
        $nonExecPath = tempnam($this->tempDir, 'test_');
        file_put_contents($nonExecPath, '#!/bin/bash');
        chmod($nonExecPath, 0644);

        $service = new BackgroundRemovalService(
            binaryPath: $nonExecPath,
            timeout: 60,
            tempDir: $this->tempDir
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not executable');

        try {
            $service->removeBackground('/input.jpg', '/output.png');
        } finally {
            @unlink($nonExecPath);
        }
    }

    public function test_executes_binary_with_correct_arguments(): void
    {
        $binaryPath = tempnam($this->tempDir, 'test_');
        file_put_contents($binaryPath, '#!/bin/bash');
        chmod($binaryPath, 0755);

        Process::fake();

        $service = new BackgroundRemovalService(
            binaryPath: $binaryPath,
            timeout: 60,
            tempDir: $this->tempDir
        );

        try {
            $service->removeBackground('/input.jpg', '/output.png');

            Process::assertRan(function ($process) use ($binaryPath) {
                return $process->command === [
                    $binaryPath,
                    '-i',
                    '/input.jpg',
                    '-o',
                    '/output.png',
                ] && $process->timeout === 60;
            });
        } finally {
            @unlink($binaryPath);
        }
    }

    public function test_throws_exception_when_process_fails(): void
    {
        $binaryPath = tempnam($this->tempDir, 'test_');
        file_put_contents($binaryPath, '#!/bin/bash');
        chmod($binaryPath, 0755);

        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'Error message'),
        ]);

        $service = new BackgroundRemovalService(
            binaryPath: $binaryPath,
            timeout: 60,
            tempDir: $this->tempDir
        );

        $this->expectException(ProcessFailedException::class);

        try {
            $service->removeBackground('/input.jpg', '/output.png');
        } finally {
            @unlink($binaryPath);
        }
    }

    public function test_removes_background_from_storage(): void
    {
        $binaryPath = tempnam($this->tempDir, 'test_');
        file_put_contents($binaryPath, '#!/bin/bash');
        chmod($binaryPath, 0755);

        Storage::fake('test-disk');
        Storage::disk('test-disk')->put('input.jpg', 'fake image content');

        Process::fake();

        $service = new BackgroundRemovalService(
            binaryPath: $binaryPath,
            timeout: 60,
            tempDir: $this->tempDir
        );

        try {
            $service->removeBackgroundFromStorage(
                inputDisk: 'test-disk',
                inputPath: 'input.jpg',
                outputDisk: 'test-disk',
                outputPath: 'output.png'
            );

            Storage::disk('test-disk')->assertExists('output.png');
        } finally {
            @unlink($binaryPath);
        }
    }
}
