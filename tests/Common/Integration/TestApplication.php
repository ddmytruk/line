<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;

/**
 * The Laravel application of the integration tests: started from app/ once per run, with the tables of the test
 * database created anew. Test classes start it in setUpBeforeClass, because starting it installs Laravel's error
 * handlers, which PHPUnit would report inside a test.
 */
final class TestApplication
{
    private static ?Application $app = null;

    public static function get(): Application
    {
        if (self::$app === null) {
            /** @var Application $app */
            $app = require __DIR__ . '/../../../app/bootstrap/app.php';
            $kernel = $app->make(Kernel::class);
            $kernel->bootstrap();
            $database = $app->make(DatabaseManager::class)->connection()->getDatabaseName();

            if ($database !== 'earning_line_test') {
                throw new \LogicException(\sprintf('The tests recreate the tables of "earning_line_test", not of "%s".', $database));
            }

            $kernel->call('migrate:fresh', ['--force' => true]);
            self::$app = $app;
        }

        return self::$app;
    }

    public static function connection(): ConnectionInterface
    {
        return self::get()->make(DatabaseManager::class)->connection();
    }

    /** A second connection to the same database, as another process would have. */
    public static function otherConnection(): ConnectionInterface
    {
        $app = self::get();
        $config = $app->make('config');
        \assert($config instanceof \Illuminate\Config\Repository);
        $default = $config->string('database.default');
        $config->set('database.connections.other', $config->array('database.connections.' . $default));

        return $app->make(DatabaseManager::class)->connection('other');
    }

    /** Empties every table of the modules. TRUNCATE is not a DELETE, so the triggers that protect stored events do not stop it. */
    public static function emptyTables(): void
    {
        $connection = self::connection();

        foreach ($connection->select('SHOW TABLES') as $row) {
            \assert(\is_object($row));
            $table = array_values((array) $row)[0];
            \assert(\is_string($table));

            if ($table !== 'migrations') {
                $connection->statement(\sprintf('TRUNCATE TABLE `%s`', $table));
            }
        }
    }
}
