<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use App\Modules\Common\Database\DatabaseProcessedCommands;
use Common\Application\Command\ProcessedCommands;
use Common\Application\Exception\CommandAlreadyProcessed;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Common\Contract\ProcessedCommandsContract;

#[CoversClass(DatabaseProcessedCommands::class)]
final class DatabaseProcessedCommandsTest extends ProcessedCommandsContract
{
    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
    }

    protected function createStore(): ProcessedCommands
    {
        return new DatabaseProcessedCommands(TestApplication::connection());
    }

    public function testACopyThatRanAtTheSameTimeIsRefusedWhenItIsStored(): void
    {
        $connection = TestApplication::connection();
        $store = new DatabaseProcessedCommands($connection);

        $connection->beginTransaction();

        try {
            self::assertNull($store->fingerprintOf('c-1'));
            // Another process carries out the same command and commits first.
            new DatabaseProcessedCommands(TestApplication::otherConnection())->add('c-1', 'fingerprint');

            $this->expectException(CommandAlreadyProcessed::class);
            $store->add('c-1', 'fingerprint');
        } finally {
            $connection->rollBack();
        }
    }
}
