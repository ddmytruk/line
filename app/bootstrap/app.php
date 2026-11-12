<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;

/*
 * The Laravel application lives inside app/: this folder is its base path, so bootstrap/, storage/ and
 * the modules sit together, and the framework-free core in src/ stays outside it.
 * Each module registers itself through its service provider, listed in providers.php.
 */
$app = Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(static function (Exceptions $exceptions): void {
        // The application serves only a JSON API, so every error is answered as JSON, whatever the client accepts.
        $exceptions->shouldRenderJsonWhen(static fn(): bool => true);
    })
    ->create();

$app->useAppPath($app->basePath());

return $app;
