<?php

declare(strict_types=1);

namespace Tests\Common\Unit\Domain;

use Common\Domain\AggregateRoot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\Diary;
use Tests\Support\Common\SomethingHappened;

#[CoversClass(AggregateRoot::class)]
final class AggregateRootTest extends TestCase
{
    public function testRecordedEventsChangeTheStateAndWaitToBeReleased(): void
    {
        $diary = Diary::open('d-1');
        $diary->write('first');

        self::assertSame(['opened', 'first'], $diary->entries);
        self::assertSame(2, $diary->version());
        self::assertEquals(
            [new SomethingHappened('d-1', 'opened'), new SomethingHappened('d-1', 'first')],
            $diary->releaseEvents(),
        );
    }

    public function testReleasingForgetsTheEventsButKeepsTheVersion(): void
    {
        $diary = Diary::open('d-1');
        $diary->releaseEvents();
        $diary->write('second');

        self::assertEquals([new SomethingHappened('d-1', 'second')], $diary->releaseEvents());
        self::assertSame([], $diary->releaseEvents());
        self::assertSame(2, $diary->version());
    }

    public function testReconstitutionReplaysTheHistoryWithoutPendingEvents(): void
    {
        $diary = Diary::reconstitute([new SomethingHappened('d-1', 'opened'), new SomethingHappened('d-1', 'first')]);

        self::assertSame(['opened', 'first'], $diary->entries);
        self::assertSame(2, $diary->version());
        self::assertSame([], $diary->releaseEvents());
    }

    public function testRejectsAnEmptyHistory(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Diary::reconstitute([]);
    }

    public function testRejectsAHistoryThatMixesAggregates(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Diary::reconstitute([new SomethingHappened('d-1', 'opened'), new SomethingHappened('d-2', 'first')]);
    }
}
