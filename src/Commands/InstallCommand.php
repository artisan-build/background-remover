<?php

declare(strict_types=1);

namespace ArtisanBuild\BackgroundRemover\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstallCommand extends Command
{
    protected $signature = 'background-removal:install
                            {--platform= : Platform to install (alpine, ubuntu, macos-arm64)}
                            {--version= : Version to install (default: latest)}';

    protected $description = 'Install the bg-remover binary for your platform';

    private const GITHUB_API = 'https://api.github.com';

    private const PLATFORM_MAP = [
        'alpine' => 'bg-remover-alpine-x86_64',
        'ubuntu' => 'bg-remover-ubuntu-x86_64',
        'macos-arm64' => 'bg-remover-macos-arm64',
    ];

    public function handle(): int
    {
        $platform = $this->option('platform') ?? $this->detectPlatform();
        $version = $this->option('version') ?? config('background-remover.github.version', 'latest');
        $repo = config('background-remover.github.repo');

        $this->info("Installing bg-remover for platform: {$platform}");

        if (! isset(self::PLATFORM_MAP[$platform])) {
            $this->error("Unsupported platform: {$platform}");
            $this->line('Supported platforms: ' . implode(', ', array_keys(self::PLATFORM_MAP)));
            $this->line('');
            $this->line('For other platforms, see: https://github.com/artisan-build/bg-remover/blob/main/FORKING.md');

            return self::FAILURE;
        }

        $binaryName = self::PLATFORM_MAP[$platform];
        $releaseInfo = $this->getRelease($repo, $version);

        if (! $releaseInfo) {
            return self::FAILURE;
        }

        $asset = collect($releaseInfo['assets'])->firstWhere('name', $binaryName);
        $checksumAsset = collect($releaseInfo['assets'])->firstWhere('name', 'checksums.txt');

        if (! $asset) {
            $this->error("Binary '{$binaryName}' not found in release {$releaseInfo['tag_name']}");

            return self::FAILURE;
        }

        $binaryPath = config('background-remover.binary_path');
        $binDir = dirname($binaryPath);

        if (! is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $this->info("Downloading {$binaryName}...");
        $binaryContent = Http::get($asset['browser_download_url'])->throw()->body();
        file_put_contents($binaryPath, $binaryContent);
        chmod($binaryPath, 0755);

        if ($checksumAsset) {
            $this->info('Verifying checksum...');
            $checksums = Http::get($checksumAsset['browser_download_url'])->throw()->body();

            if (! $this->verifyChecksum($binaryPath, $binaryName, $checksums)) {
                @unlink($binaryPath);
                $this->error('Checksum verification failed!');

                return self::FAILURE;
            }

            $this->info('Checksum verified successfully.');
        }

        $this->info("Binary installed successfully at: {$binaryPath}");

        return self::SUCCESS;
    }

    private function detectPlatform(): string
    {
        $os = PHP_OS_FAMILY;
        $platform = strtolower($os);

        if ($platform === 'darwin') {
            return 'macos-arm64';
        }

        if ($platform === 'linux') {
            if (file_exists('/etc/alpine-release')) {
                return 'alpine';
            }

            return 'ubuntu';
        }

        throw new RuntimeException("Unable to auto-detect platform. Please specify with --platform option.");
    }

    private function getRelease(string $repo, string $version): ?array
    {
        $endpoint = $version === 'latest'
            ? "/repos/{$repo}/releases/latest"
            : "/repos/{$repo}/releases/tags/{$version}";

        try {
            $response = Http::get(self::GITHUB_API . $endpoint)->throw();

            return $response->json();
        } catch (\Exception $e) {
            $this->error("Failed to fetch release information: {$e->getMessage()}");

            return null;
        }
    }

    private function verifyChecksum(string $binaryPath, string $binaryName, string $checksums): bool
    {
        $actualHash = hash_file('sha256', $binaryPath);

        foreach (explode("\n", $checksums) as $line) {
            if (str_contains($line, $binaryName)) {
                [$expectedHash] = explode(' ', trim($line), 2);

                return hash_equals($expectedHash, $actualHash);
            }
        }

        return false;
    }
}
