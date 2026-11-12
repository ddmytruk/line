<?php

declare(strict_types=1);

namespace Tests\Common\Unit\Application;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use App\Modules\Common\InMemory\InMemoryTransaction;
use Common\Application\Command\Command;
use Common\Application\Command\CommandBus;
use Common\Application\Command\ProcessedCommands;
use Common\Application\EventStore;
use Common\Application\Exception\CommandIdReused;
use Common\Application\Exception\ConcurrencyConflict;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\InterleavingEventStore;
use Tests\Support\Common\SomethingHappened;
use Tests\Support\Common\TakeNote;
use Tests\Support\Common\TakeNoteSafely;

/** A command is carried out at most once per id, in a transaction, and a failure leaves no trace. */
#[CoversClass(CommandBus::class)]
final class CommandBusTest extends TestCase
{
    private InMemoryEventStore $store;

    private InterleavingEventStore $others;

    private InMemoryProcessedCommands $processed;

    private bool $failAfterWriting = false;

    protected function setUp(): void
    {
        $this->store = new InMemoryEventStore();
        $this->others = new InterleavingEventStore($this->store);
        $this->processed = new InMemoryProcessedCommands();
    }

    public function testCarriesOutACommand(): void
    {
        $this->bus()->dispatch(new TakeNote('c-1', 'first'));

        self::assertSame(['first'], $this->notes());
        self::assertNotNull($this->processed->fingerprintOf('c-1'));
    }

    public function testARepeatedCommandDoesNothing(): void
    {
        $bus = $this->bus();
        $bus->dispatch(new TakeNote('c-1', 'first'));
        $bus->dispatch(new TakeNote('c-1', 'first'));

        self::assertSame(['first'], $this->notes());
    }

    public function testRefusesAKnownIdWithOtherContentAndDoesNothing(): void
    {
        $bus = $this->bus();
        $bus->dispatch(new TakeNote('c-1', 'first'));

        try {
            $bus->dispatch(new TakeNote('c-1', 'something else'));
            self::fail('A reused command id must be refused.');
        } catch (CommandIdReused) {
        }

        self::assertSame(['first'], $this->notes());
    }

    public function testARefusedCommandCanBeCorrectedAndSentAgainUnderTheSameId(): void
    {
        $bus = $this->bus();
        $this->failAfterWriting = true;

        try {
            $bus->dispatch(new TakeNote('c-1', 'wrong'));
            self::fail('The handler failure must reach the sender.');
        } catch (\RuntimeException) {
        }

        $this->failAfterWriting = false;
        $bus->dispatch(new TakeNote('c-1', 'corrected'));

        self::assertSame(['corrected'], $this->notes());
    }

    public function testAFailedCommandLeavesNoTraceAndCanBeSentAgain(): void
    {
        $bus = $this->bus();
        $this->failAfterWriting = true;

        try {
            $bus->dispatch(new TakeNote('c-1', 'first'));
            self::fail('The handler failure must reach the sender.');
        } catch (\RuntimeException $failure) {
            self::assertSame('Handler failed after writing.', $failure->getMessage());
        }

        self::assertSame([], $this->notes());
        self::assertNull($this->processed->fingerprintOf('c-1'));

        $this->failAfterWriting = false;
        $bus->dispatch(new TakeNote('c-1', 'first'));
        self::assertSame(['first'], $this->notes());
    }

    public function testACommandWithoutAHandlerIsAProgrammingError(): void
    {
        $bus = new CommandBus(new InMemoryTransaction([]), $this->processed, []);

        $this->expectException(\LogicException::class);
        $bus->dispatch(new TakeNote('c-1', 'first'));
    }

    public function testAConflictIsReturnedToASenderWhoDecidedOnWhatTheySaw(): void
    {
        $this->others->beforeNextAppend(self::otherWriter(...));

        try {
            $this->bus()->dispatch(new TakeNote('c-1', 'mine'));
            self::fail('The conflict must reach the sender.');
        } catch (ConcurrencyConflict) {
        }

        self::assertSame(['other'], $this->notes());
        self::assertNull($this->processed->fingerprintOf('c-1'));
    }

    public function testACommandThatIsSafeToRetryIsRunAgainOnTheNewState(): void
    {
        $this->others->beforeNextAppend(self::otherWriter(...));

        $this->bus()->dispatch(new TakeNoteSafely('c-1', 'mine'));

        self::assertSame(['other', 'mine'], $this->notes());
    }

    public function testACommandThatIsSafeToRetryIsRetriedOnlyOnce(): void
    {
        $this->others->beforeNextAppend(self::otherWriter(...));
        $this->others->beforeNextAppend(self::otherWriter(...));

        $this->expectException(ConcurrencyConflict::class);
        $this->bus()->dispatch(new TakeNoteSafely('c-1', 'mine'));
    }

    public function testACommandThatAnotherRunCarriedOutFirstIsSkipped(): void
    {
        $bus = $this->busWithARunThatRecordsTheIdFirst(sameContent: true);

        $bus->dispatch(new TakeNote('c-1', 'first'));

        self::assertSame([], $this->notes(), 'Only the run that recorded the id first may keep what it wrote.');
    }

    public function testAnotherRunThatRecordedTheIdFirstWithOtherContentMakesThisOneAReusedId(): void
    {
        $bus = $this->busWithARunThatRecordsTheIdFirst(sameContent: false);

        try {
            $bus->dispatch(new TakeNote('c-1', 'first'));
            self::fail('The sender must learn that its command was not carried out.');
        } catch (CommandIdReused) {
        }

        self::assertSame([], $this->notes());
    }

    /**
     * Another run of a command with the same id checks the id at the same moment, so both see it as new,
     * and records the id first. The other run commits elsewhere, so its record outlives this run's rollback.
     */
    private function busWithARunThatRecordsTheIdFirst(bool $sameContent): CommandBus
    {
        $racing = new class ($this->processed, $sameContent) implements ProcessedCommands {
            public function __construct(
                private readonly ProcessedCommands $inner,
                private readonly bool $sameContent,
            ) {}

            public function fingerprintOf(string $commandId): ?string
            {
                return $this->inner->fingerprintOf($commandId);
            }

            public function add(string $commandId, string $fingerprint): void
            {
                if ($this->inner->fingerprintOf($commandId) === null) {
                    $this->inner->add($commandId, $this->sameContent ? $fingerprint : 'fingerprint of other content');
                }

                $this->inner->add($commandId, $fingerprint);
            }
        };

        return new CommandBus(new InMemoryTransaction([$this->others]), $racing, [TakeNote::class => $this->handler(...)]);
    }

    private function bus(): CommandBus
    {
        return new CommandBus(
            new InMemoryTransaction([$this->others, $this->processed]),
            $this->processed,
            [TakeNote::class => $this->handler(...), TakeNoteSafely::class => $this->handler(...)],
        );
    }

    private function handler(Command $command): void
    {
        \assert($command instanceof TakeNote || $command instanceof TakeNoteSafely);
        $this->others->append('notes', \count($this->store->load('notes')), [new SomethingHappened('notes', $command->text)]);

        if ($this->failAfterWriting) {
            throw new \RuntimeException('Handler failed after writing.');
        }
    }

    private static function otherWriter(EventStore $store): void
    {
        $store->append('notes', \count($store->load('notes')), [new SomethingHappened('notes', 'other')]);
    }

    /** @return list<string> */
    private function notes(): array
    {
        $notes = [];

        foreach ($this->store->load('notes') as $event) {
            \assert($event instanceof SomethingHappened);
            $notes[] = $event->what;
        }

        return $notes;
    }
}
