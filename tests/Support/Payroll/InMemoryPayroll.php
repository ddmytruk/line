<?php

declare(strict_types=1);

namespace Tests\Support\Payroll;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use App\Modules\Common\InMemory\InMemoryTransaction;
use App\Modules\Payroll\InMemory\InMemoryEarningLineHistories;
use Common\Application\Command\CommandBus;
use Common\Application\EventStore;
use Common\Application\ReadModel\ProjectingEventStore;
use Common\Application\ReadModel\ReadModelRebuild;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\PayrollCommandHandlers;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\History\EarningLineHistoryProjector;
use Payroll\Application\Query\GetEarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistoryHandler;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Tests\Support\Common\FixedClock;
use Tests\Support\Common\InterleavingEventStore;

/** The Payroll module wired with in-memory adapters, as tests use it. */
final readonly class InMemoryPayroll
{
    public InMemoryEventStore $store;

    public InterleavingEventStore $others;

    public InMemoryEarningLineHistories $histories;

    public EarningLineHistoryProjector $projector;

    public FixedClock $clock;

    public EarningLines $lines;

    public CommandBus $bus;

    public ReadModelRebuild $rebuild;

    public GetEarningLineHistoryHandler $getEarningLineHistory;

    public function __construct()
    {
        $this->store = new InMemoryEventStore();
        $this->others = new InterleavingEventStore($this->store);
        $this->histories = new InMemoryEarningLineHistories();
        $this->projector = new EarningLineHistoryProjector($this->histories);
        $this->clock = new FixedClock();
        $this->lines = new EarningLines(new ProjectingEventStore($this->others, [$this->projector]));
        $processed = new InMemoryProcessedCommands();
        // The histories are restored first, so what another writer committed is projected again on top of them.
        $transaction = new InMemoryTransaction([$this->histories, $this->others, $processed]);
        $this->bus = new CommandBus(
            $transaction,
            $processed,
            PayrollCommandHandlers::create($this->lines, $this->clock),
        );
        $this->rebuild = new ReadModelRebuild($transaction, $this->store, [$this->projector]);
        $this->getEarningLineHistory = new GetEarningLineHistoryHandler($this->histories);
    }

    public function line(string $id = Lines::LINE_ID): EarningLine
    {
        return $this->lines->get(new EarningLineId($id));
    }

    public function history(string $id = Lines::LINE_ID): EarningLineHistory
    {
        return ($this->getEarningLineHistory)(new GetEarningLineHistory($id));
    }

    /** @return int the version a specialist sees when looking at the line */
    public function versionOf(string $id = Lines::LINE_ID): int
    {
        return \count($this->store->load($id));
    }

    /** Lets another writer change the stream right before the next save; what it writes is projected too. */
    public function anotherWriterFirst(\Closure $write): void
    {
        $projector = $this->projector;
        $this->others->beforeNextAppend(static function (EventStore $store) use ($write, $projector): void {
            $write(new EarningLines(new ProjectingEventStore($store, [$projector])));
        });
    }
}
