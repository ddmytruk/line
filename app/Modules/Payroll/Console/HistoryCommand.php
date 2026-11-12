<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Console;

use App\Modules\Payroll\Cli\HistoryView;
use Illuminate\Console\Command;
use Payroll\Application\Query\GetEarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistoryHandler;

/** Prints what a specialist reads: the history of a line, its full log and the version to adjust. */
final class HistoryCommand extends Command
{
    protected $signature = 'payroll:history {line : id of the line (UUID)}';

    protected $description = 'Show the history of an earning line';

    public function handle(GetEarningLineHistoryHandler $getHistory): int
    {
        return Refusals::reported($this, function () use ($getHistory): int {
            $history = $getHistory(new GetEarningLineHistory(CommandOptions::text($this->argument('line'))));
            $this->line(\sprintf('Line %s, version %d', $history->lineId, $history->version));
            $this->newLine();
            $this->line(HistoryView::history($history));
            $this->line(HistoryView::log($history));

            return self::SUCCESS;
        });
    }
}
