<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use Illuminate\Database\DatabaseManager;

/** The Laravel application starts from app/ and reaches its MySQL database. */
final class ApplicationTest extends LaravelTestCase
{
    public function testStartsFromTheAppFolder(): void
    {
        self::assertSame(realpath(__DIR__ . '/../../../app'), $this->application()->basePath());
        self::assertSame($this->application()->basePath(), $this->application()->path());
    }

    public function testReachesTheTestDatabase(): void
    {
        $connection = $this->application()->make(DatabaseManager::class)->connection();

        self::assertSame('mysql', $connection->getDriverName());
        self::assertSame('earning_line_test', $connection->getDatabaseName());
        self::assertEquals([(object) ['answer' => 1]], $connection->select('select 1 as answer'));
    }
}
