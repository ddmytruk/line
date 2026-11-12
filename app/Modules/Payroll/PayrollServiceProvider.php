<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Modules\Common\Database\EventSerializer;
use App\Modules\Payroll\Console\AdjustCommand;
use App\Modules\Payroll\Console\HistoryCommand;
use App\Modules\Payroll\Console\RebuildCommand;
use App\Modules\Payroll\Console\RecordValueCommand;
use App\Modules\Payroll\Database\DatabaseEarningLineHistories;
use App\Modules\Payroll\Database\PayrollEventSerializer;
use App\Modules\Payroll\Http\PayrollRoutes;
use Common\Application\Clock;
use Common\Application\Command\CommandBus;
use Common\Application\Command\ProcessedCommands;
use Common\Application\EventStore;
use Common\Application\ReadModel\ProjectingEventStore;
use Common\Application\ReadModel\ReadModelRebuild;
use Common\Application\Transaction;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\ServiceProvider;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\PayrollCommandHandlers;
use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistoryProjector;

/**
 * The Payroll module on the database: the same wiring as the in-memory module, with the stores of the database,
 * plus its artisan commands and HTTP routes. Payroll is the only module with events, so its serializer is the one
 * the event store uses, and the command bus carries out its commands.
 */
final class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventSerializer::class, PayrollEventSerializer::class);
        $this->app->singleton(EarningLineHistories::class, DatabaseEarningLineHistories::class);
        $this->app->bind(CommandBus::class, static function (Application $app): CommandBus {
            $store = new ProjectingEventStore($app->make(EventStore::class), [$app->make(EarningLineHistoryProjector::class)]);

            return new CommandBus(
                $app->make(Transaction::class),
                $app->make(ProcessedCommands::class),
                PayrollCommandHandlers::create(new EarningLines($store), $app->make(Clock::class)),
            );
        });
        $this->app->bind(ReadModelRebuild::class, static fn(Application $app): ReadModelRebuild => new ReadModelRebuild(
            $app->make(Transaction::class),
            $app->make(EventStore::class),
            [$app->make(EarningLineHistoryProjector::class)],
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->commands([RecordValueCommand::class, AdjustCommand::class, HistoryCommand::class, RebuildCommand::class]);
        PayrollRoutes::register($this->app->make(Registrar::class));
    }
}
