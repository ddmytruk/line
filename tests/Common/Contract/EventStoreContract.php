<?php

declare(strict_types=1);

namespace Tests\Common\Contract;

use Common\Application\EventStore;
use Common\Application\Exception\ConcurrencyConflict;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\SomethingHappened;

/** What every event store must guarantee. Each implementation runs these tests by extending this class. */
abstract class EventStoreContract extends TestCase
{
    abstract protected function createStore(): EventStore;

    public function testAStreamThatDoesNotExistIsEmpty(): void
    {
        self::assertSame([], $this->createStore()->load('missing'));
    }

    public function testLoadsEventsInTheOrderTheyWereAppended(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [self::event('a', 'first'), self::event('a', 'second')]);
        $store->append('a', 2, [self::event('a', 'third')]);

        self::assertEquals([self::event('a', 'first'), self::event('a', 'second'), self::event('a', 'third')], $store->load('a'));
    }

    public function testRefusesAChangeBasedOnAnOutdatedVersionAndWritesNothing(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [self::event('a', 'first')]);
        $store->append('a', 1, [self::event('a', 'second')]);

        try {
            $store->append('a', 1, [self::event('a', 'late')]);
            self::fail('An outdated version must be refused.');
        } catch (ConcurrencyConflict) {
        }

        self::assertEquals([self::event('a', 'first'), self::event('a', 'second')], $store->load('a'));
    }

    public function testRefusesAVersionTheStreamHasNotReached(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [self::event('a', 'first')]);

        $this->expectException(ConcurrencyConflict::class);
        $store->append('a', 2, [self::event('a', 'second')]);
    }

    public function testRefusesAVersionAboveZeroForAStreamThatDoesNotExist(): void
    {
        $store = $this->createStore();

        try {
            $store->append('a', 3, [self::event('a', 'first')]);
            self::fail('A stream that does not exist is at version 0.');
        } catch (ConcurrencyConflict) {
        }

        self::assertSame([], $store->load('a'));
    }

    public function testRefusesToStartAStreamThatAlreadyExists(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [self::event('a', 'created')]);

        $this->expectException(ConcurrencyConflict::class);
        $store->append('a', 0, [self::event('a', 'created again')]);
    }

    public function testRefusesAnEventOfAnotherAggregateAndWritesNoneOfTheBatch(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [self::event('a', 'first')]);

        foreach (['new stream' => ['c', 0], 'existing stream' => ['a', 1]] as $case => [$stream, $version]) {
            try {
                $store->append($stream, $version, [self::event($stream, 'second'), self::event('b', 'stranger')]);
                self::fail(\sprintf('%s: an event of another aggregate must be refused.', $case));
            } catch (\InvalidArgumentException) {
            }
        }

        self::assertEquals([self::event('a', 'first')], $store->load('a'));
        self::assertSame([], $store->load('b'));
        self::assertSame([], $store->load('c'));
    }

    public function testStreamsDoNotAffectEachOther(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, [self::event('a', 'first')]);
        $store->append('b', 0, [self::event('b', 'first'), self::event('b', 'second')]);

        self::assertEquals([self::event('a', 'first')], $store->load('a'));
        self::assertCount(2, $store->load('b'));
    }

    public function testAppendingNothingChangesNothingWhateverTheVersion(): void
    {
        $store = $this->createStore();
        $store->append('a', 0, []);
        $store->append('b', 0, [self::event('b', 'first')]);
        $store->append('b', 7, []);

        self::assertSame([], $store->load('a'));
        self::assertCount(1, $store->load('b'));
    }

    private static function event(string $id, string $what): SomethingHappened
    {
        return new SomethingHappened($id, $what);
    }
}
