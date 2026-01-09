<?php

declare(strict_types=1);

namespace ArtisanBuild\BackgroundRemover\Providers;

use ArtisanBuild\BackgroundRemover\Commands\InstallCommand;
use ArtisanBuild\BackgroundRemover\Services\BackgroundRemovalService;
use Illuminate\Support\ServiceProvider;

class BackgroundRemoverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/background-remover.php',
            'background-remover'
        );

        $this->app->singleton(BackgroundRemovalService::class, function ($app) {
            return new BackgroundRemovalService(
                $app['config']->get('background-remover.binary_path'),
                $app['config']->get('background-remover.timeout'),
                $app['config']->get('background-remover.temp_dir')
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/background-remover.php' => config_path('background-remover.php'),
            ], 'background-remover-config');

            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
