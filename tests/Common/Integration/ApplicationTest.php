<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use App\Modules\Common\Database\DatabaseProcessedCommands;
use App\Modules\Common\Database\DatabaseTransaction;
use Common\Application\Command\ProcessedCommands;
use Common\Application\Transaction;
use Illuminate\Database\MySqlConnection;
use PHPUnit\Framework\TestCase;

/** The Laravel application starts from app/, reaches its MySQL database and puts the shared ports on it. */
final class ApplicationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    public function testStartsFromTheAppFolder(): void
    {
        $app = TestApplication::get();

        self::assertSame(realpath(__DIR__ . '/../../../app'), $app->basePath());
        self::assertSame($app->basePath(), $app->path());
    }

    public function testReachesTheTestDatabase(): void
    {
        $connection = TestApplication::connection();

        self::assertInstanceOf(MySqlConnection::class, $connection);
        self::assertSame('earning_line_test', $connection->getDatabaseName());
        self::assertEquals([(object) ['answer' => 1]], $connection->select('select 1 as answer'));
    }

    public function testPutsTheSharedPortsOnTheDatabase(): void
    {
        $app = TestApplication::get();

        self::assertInstanceOf(DatabaseProcessedCommands::class, $app->make(ProcessedCommands::class));
        self::assertInstanceOf(DatabaseTransaction::class, $app->make(Transaction::class));
    }
}
