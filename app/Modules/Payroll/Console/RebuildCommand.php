<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Console;

use Common\Application\ReadModel\ReadModelRebuild;
use Illuminate\Console\Command;

/** Builds the line histories again from every stored event, for example after the way they are built was fixed. */
final class RebuildCommand extends Command
{
    protected $signature = 'payroll:rebuild';

    protected $description = 'Build the histories of all earning lines again from the stored events';

    public function handle(ReadModelRebuild $rebuild): int
    {
        $rebuild->run();
        $this->info('The line histories were built again from the stored events.');

        return self::SUCCESS;
    }
}
