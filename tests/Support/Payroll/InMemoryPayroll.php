<?php

declare(strict_types=1);

namespace Tests\Support\Payroll;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use App\Modules\Common\InMemory\InMemoryTransaction;
use Common\Application\Command\CommandBus;
use Common\Application\EventStore;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\PayrollCommandHandlers;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Tests\Support\Common\FixedClock;
use Tests\Support\Common\InterleavingEventStore;

/** The Payroll module wired with in-memory adapters, as tests use it. */
final readonly class InMemoryPayroll
{
    public InMemoryEventStore $store;

    public InterleavingEventStore $others;

    public FixedClock $clock;

    public EarningLines $lines;

    public CommandBus $bus;

    public function __construct()
    {
        $this->store = new InMemoryEventStore();
        $this->others = new InterleavingEventStore($this->store);
        $this->clock = new FixedClock();
        $this->lines = new EarningLines($this->others);
        $processed = new InMemoryProcessedCommands();
        $this->bus = new CommandBus(
            new InMemoryTransaction([$this->others, $processed]),
            $processed,
            PayrollCommandHandlers::create($this->lines, $this->clock),
        );
    }

    public function line(string $id = Lines::LINE_ID): EarningLine
    {
        return $this->lines->get(new EarningLineId($id));
    }

    /** @return int the version a specialist sees when looking at the line */
    public function versionOf(string $id = Lines::LINE_ID): int
    {
        return \count($this->store->load($id));
    }

    /** Lets another writer change the stream right before the next save. */
    public function anotherWriterFirst(\Closure $write): void
    {
        $this->others->beforeNextAppend(static function (EventStore $store) use ($write): void {
            $write(new EarningLines($store));
        });
    }
}
