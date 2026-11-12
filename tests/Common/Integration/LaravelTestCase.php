<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Base for the tests of the Laravel adapters.
 *
 * The application lives in app/, not at the root that Laravel would guess, so it is started here
 * from app/bootstrap/app.php. Laravel's TestCase cleans up after each test.
 */
abstract class LaravelTestCase extends TestCase
{
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../../../app/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /** The application started for the current test. */
    protected function application(): Application
    {
        self::assertNotNull($this->app);

        return $this->app;
    }
}
