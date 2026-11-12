<?php

declare(strict_types=1);

namespace App\Modules\Payroll\InMemory;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use App\Modules\Common\InMemory\InMemoryTransaction;
use Common\Application\Clock;
use Common\Application\Command\CommandBus;
use Common\Application\ReadModel\ProjectingEventStore;
use Common\Application\ReadModel\ReadModelRebuild;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\PayrollCommandHandlers;
use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistoryProjector;
use Payroll\Application\Query\GetEarningLineHistoryHandler;

/** The Payroll module wired with in-memory adapters: everything lives as long as the running process. */
final readonly class InMemoryPayrollModule
{
    public CommandBus $bus;

    /** Exposed to drop the histories before a rebuild; reading goes through $getEarningLineHistory. */
    public EarningLineHistories $histories;

    public ReadModelRebuild $rebuild;

    public GetEarningLineHistoryHandler $getEarningLineHistory;

    public function __construct(Clock $clock)
    {
        $store = new InMemoryEventStore();
        $histories = new InMemoryEarningLineHistories();
        $processed = new InMemoryProcessedCommands();
        $projector = new EarningLineHistoryProjector($histories);

        $transaction = new InMemoryTransaction([$store, $histories, $processed]);
        $this->bus = new CommandBus(
            $transaction,
            $processed,
            PayrollCommandHandlers::create(new EarningLines(new ProjectingEventStore($store, [$projector])), $clock),
        );
        $this->histories = $histories;
        $this->rebuild = new ReadModelRebuild($transaction, $store, [$projector]);
        $this->getEarningLineHistory = new GetEarningLineHistoryHandler($histories);
    }
}
