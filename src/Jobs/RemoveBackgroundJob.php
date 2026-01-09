<?php

declare(strict_types=1);

namespace ArtisanBuild\BackgroundRemover\Jobs;

use ArtisanBuild\BackgroundRemover\Services\BackgroundRemovalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveBackgroundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $inputDisk,
        public readonly string $inputPath,
        public readonly string $outputDisk,
        public readonly string $outputPath
    ) {}

    public function handle(BackgroundRemovalService $service): void
    {
        $service->removeBackgroundFromStorage(
            $this->inputDisk,
            $this->inputPath,
            $this->outputDisk,
            $this->outputPath
        );
    }
}
