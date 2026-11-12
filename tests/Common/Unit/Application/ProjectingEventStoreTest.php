<?php

declare(strict_types=1);

namespace Tests\Common\Unit\Application;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryTransaction;
use Common\Application\Exception\ConcurrencyConflict;
use Common\Application\ReadModel\ProjectingEventStore;
use Common\Application\ReadModel\Projector;
use Common\Application\ReadModel\ReadModelRebuild;
use Common\Domain\DomainEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\RecordingProjector;
use Tests\Support\Common\SomethingHappened;

/** Projectors see every appended event exactly once, and only events that were appended. */
#[CoversClass(ProjectingEventStore::class)]
#[CoversClass(ReadModelRebuild::class)]
final class ProjectingEventStoreTest extends TestCase
{
    public function testHandsEveryAppendedEventToEveryProjectorInOrder(): void
    {
        $first = new RecordingProjector();
        $second = new RecordingProjector();
        $store = new ProjectingEventStore(new InMemoryEventStore(), [$first, $second]);

        $store->append('a', 0, [new SomethingHappened('a', 'one'), new SomethingHappened('a', 'two')]);
        $store->append('b', 0, [new SomethingHappened('b', 'three')]);

        self::assertSame(['one', 'two', 'three'], $first->seen);
        self::assertSame(['one', 'two', 'three'], $second->seen);
    }

    public function testARefusedAppendIsNotProjected(): void
    {
        $projector = new RecordingProjector();
        $store = new ProjectingEventStore(new InMemoryEventStore(), [$projector]);
        $store->append('a', 0, [new SomethingHappened('a', 'one')]);

        try {
            $store->append('a', 0, [new SomethingHappened('a', 'refused')]);
            self::fail('An outdated version must be refused.');
        } catch (ConcurrencyConflict) {
        }

        self::assertSame(['one'], $projector->seen);
    }

    public function testAFailingProjectorDropsTheEventsOfTheTransaction(): void
    {
        $inner = new InMemoryEventStore();
        $recording = new RecordingProjector();
        $failing = new class implements Projector {
            public function project(DomainEvent $event): void
            {
                throw new \RuntimeException('Projector failed.');
            }

            public function reset(): void {}
        };
        $store = new ProjectingEventStore($inner, [$recording, $failing]);

        try {
            new InMemoryTransaction([$inner])->run(static function () use ($store): void {
                $store->append('a', 0, [new SomethingHappened('a', 'one')]);
            });
            self::fail('The failure of the projector must reach the caller.');
        } catch (\RuntimeException) {
        }

        self::assertSame([], $inner->load('a'));
        self::assertSame([], [...$inner->all()]);
    }

    public function testARebuildForgetsTheModelAndProjectsEveryStoredEventAgain(): void
    {
        $inner = new InMemoryEventStore();
        $projector = new RecordingProjector();
        $store = new ProjectingEventStore($inner, [$projector]);
        $store->append('a', 0, [new SomethingHappened('a', 'one')]);
        $store->append('b', 0, [new SomethingHappened('b', 'two')]);
        $projector->project(new SomethingHappened('a', 'not stored'));

        new ReadModelRebuild(new InMemoryTransaction([$inner]), $inner, [$projector])->run();

        self::assertSame(['one', 'two'], $projector->seen);
    }
}
