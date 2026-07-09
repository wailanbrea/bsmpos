<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JsonException;
use LogicException;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulesPath = config('modules.path');

        if (! is_string($modulesPath) || ! is_dir($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $this->registerRoutes($modulePath);
        }
    }

    private function registerRoutes(string $modulePath): void
    {
        $manifestPath = $modulePath.'/module.json';
        $routesPath = $modulePath.'/routes.php';

        if (! is_file($manifestPath) || ! is_file($routesPath)) {
            return;
        }

        try {
            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LogicException("Invalid module manifest [{$manifestPath}].", previous: $exception);
        }

        if (! is_array($manifest) || ! isset($manifest['code']) || ! is_string($manifest['code'])) {
            throw new LogicException("Module manifest [{$manifestPath}] requires a string code.");
        }

        Route::middleware(config('modules.route_middleware'))
            ->prefix(config('modules.api_prefix'))
            ->group($routesPath);
    }
}
