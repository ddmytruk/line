<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use App\Modules\Common\Database\DatabaseEventStore;
use App\Modules\Common\Database\DatabaseProcessedCommands;
use App\Modules\Common\Database\DatabaseTransaction;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Common\Contract\TransactionContract;
use Tests\Support\Common\SomethingHappenedSerializer;

#[CoversClass(DatabaseTransaction::class)]
final class DatabaseTransactionTest extends TransactionContract
{
    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
    }

    protected function createTransactionAndStores(): array
    {
        $connection = TestApplication::connection();

        return [
            new DatabaseTransaction($connection),
            new DatabaseEventStore($connection, new SomethingHappenedSerializer()),
            new DatabaseProcessedCommands($connection),
        ];
    }
}
