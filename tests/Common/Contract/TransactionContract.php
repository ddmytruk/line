<?php

declare(strict_types=1);

namespace Tests\Common\Contract;

use Common\Application\Command\ProcessedCommands;
use Common\Application\EventStore;
use Common\Application\Transaction;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\SomethingHappened;

/**
 * What every transaction must guarantee for the stores it covers: work is kept together, or none of it is kept.
 * Each implementation runs these tests by extending this class.
 */
abstract class TransactionContract extends TestCase
{
    /** @return array{Transaction, EventStore, ProcessedCommands} a transaction and the empty stores it covers */
    abstract protected function createTransactionAndStores(): array;

    public function testKeepsWhatSuccessfulWorkWroteAndReturnsItsResult(): void
    {
        [$transaction, $store] = $this->createTransactionAndStores();

        $result = $transaction->run(static function () use ($store): int {
            $store->append('a', 0, [new SomethingHappened('a', 'first')]);

            return \count($store->load('a'));
        });

        self::assertSame(1, $result);
        self::assertCount(1, $store->load('a'));
    }

    public function testPutsEveryStoreBackWhenTheWorkFails(): void
    {
        [$transaction, $store, $processed] = $this->createTransactionAndStores();
        $store->append('a', 0, [new SomethingHappened('a', 'before')]);
        $failure = new \RuntimeException('Failed halfway.');

        $caught = null;

        try {
            $transaction->run(static function () use ($store, $processed, $failure): void {
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

    public function testAFailedInnerRunUndoesOnlyItsOwnWork(): void
    {
        [$transaction, $store, $processed] = $this->createTransactionAndStores();

        $transaction->run(static function () use ($transaction, $store, $processed): void {
            $store->append('a', 0, [new SomethingHappened('a', 'outer')]);

            try {
                $transaction->run(static function () use ($store, $processed): void {
                    $store->append('b', 0, [new SomethingHappened('b', 'inner')]);
                    $processed->add('c-1', 'fingerprint');

                    throw new \RuntimeException('Inner work failed.');
                });
            } catch (\RuntimeException) {
            }

            $processed->add('c-2', 'fingerprint');
        });

        self::assertEquals([new SomethingHappened('a', 'outer')], [...$store->all()]);
        self::assertNull($processed->fingerprintOf('c-1'));
        self::assertSame('fingerprint', $processed->fingerprintOf('c-2'));
    }
}
