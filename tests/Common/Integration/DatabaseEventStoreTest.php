<?php

declare(strict_types=1);

namespace Tests\Common\Integration;

use App\Modules\Common\Database\DatabaseEventStore;
use Common\Application\EventStore;
use Common\Application\Exception\ConcurrencyConflict;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Common\Contract\EventStoreContract;
use Tests\Support\Common\SomethingHappened;
use Tests\Support\Common\SomethingHappenedSerializer;

#[CoversClass(DatabaseEventStore::class)]
final class DatabaseEventStoreTest extends EventStoreContract
{
    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
    }

    protected function createStore(): EventStore
    {
        return new DatabaseEventStore(TestApplication::connection(), new SomethingHappenedSerializer());
    }

    public function testAWriterThatCheckedTheVersionTogetherWithAnotherOneGetsAConflict(): void
    {
        $connection = TestApplication::connection();
        $store = new DatabaseEventStore($connection, new SomethingHappenedSerializer());
        $store->append('a', 0, [new SomethingHappened('a', 'first')]);

        $connection->beginTransaction();

        try {
            // This writer has read version 1; another process appends to the stream and commits first.
            self::assertCount(1, $store->load('a'));
            new DatabaseEventStore(TestApplication::otherConnection(), new SomethingHappenedSerializer())
                ->append('a', 1, [new SomethingHappened('a', 'other')]);

            $this->expectException(ConcurrencyConflict::class);
            $store->append('a', 1, [new SomethingHappened('a', 'late')]);
        } finally {
            $connection->rollBack();
            self::assertEquals([new SomethingHappened('a', 'first'), new SomethingHappened('a', 'other')], $store->load('a'));
        }
    }

    public function testListsAllEventsAcrossPages(): void
    {
        $store = new DatabaseEventStore(TestApplication::connection(), new SomethingHappenedSerializer(), pageSize: 2);
        $store->append('a', 0, [new SomethingHappened('a', '1'), new SomethingHappened('a', '2')]);
        $store->append('b', 0, [new SomethingHappened('b', '3')]);
        $store->append('a', 2, [new SomethingHappened('a', '4'), new SomethingHappened('a', '5')]);

        self::assertSame(['1', '2', '3', '4', '5'], array_map(static fn(object $event): string => $event instanceof SomethingHappened ? $event->what : '', [...$store->all()]));
    }

    /** @return iterable<string, array{string}> */
    public static function changesOfStoredEvents(): iterable
    {
        yield 'update' => ["UPDATE events SET payload = '{}'"];
        yield 'delete' => ['DELETE FROM events'];
    }

    #[DataProvider('changesOfStoredEvents')]
    public function testTheDatabaseRefusesToChangeOrDeleteStoredEvents(string $change): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [new SomethingHappened('a', 'first')]);

        try {
            TestApplication::connection()->statement($change);
            self::fail('A stored event must not change.');
        } catch (QueryException) {
        }

        self::assertEquals([new SomethingHappened('a', 'first')], $store->load('a'));
    }
}
