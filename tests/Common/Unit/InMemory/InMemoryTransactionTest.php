<?php

declare(strict_types=1);

namespace Tests\Common\Unit\InMemory;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use App\Modules\Common\InMemory\InMemoryTransaction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\SomethingHappened;

/** Work in a transaction is kept together, or none of it is kept. */
#[CoversClass(InMemoryTransaction::class)]
final class InMemoryTransactionTest extends TestCase
{
    public function testKeepsWhatSuccessfulWorkWroteAndReturnsItsResult(): void
    {
        $store = new InMemoryEventStore();

        $result = new InMemoryTransaction([$store])->run(static function () use ($store): int {
            $store->append('a', 0, [new SomethingHappened('a', 'first')]);

            return \count($store->load('a'));
        });

        self::assertSame(1, $result);
        self::assertCount(1, $store->load('a'));
    }

    public function testPutsEveryStorageBackWhenTheWorkFails(): void
    {
        $store = new InMemoryEventStore();
        $store->append('a', 0, [new SomethingHappened('a', 'before')]);
        $processed = new InMemoryProcessedCommands();
        $failure = new \RuntimeException('Failed halfway.');

        $caught = null;

        try {
            new InMemoryTransaction([$store, $processed])->run(static function () use ($store, $processed, $failure): void {
                $store->append('a', 1, [new SomethingHappened('a', 'during')]);
                $store->append('b', 0, [new SomethingHappened('b', 'during')]);
                $processed->add('c-1', 'fingerprint');

                throw $failure;
            });
        } catch (\RuntimeException $caught) {
        }

        self::assertSame($failure, $caught, 'The failure must reach the caller.');
        self::assertCount(1, $store->load('a'));
        self::assertSame([], $store->load('b'));
        self::assertEquals([new SomethingHappened('a', 'before')], [...$store->all()], 'dropped events must not come back in a rebuild');
        self::assertNull($processed->fingerprintOf('c-1'));
    }
}
