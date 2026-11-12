<?php

declare(strict_types=1);

namespace App\Modules\Common;

use App\Modules\Common\Clock\SystemClock;
use App\Modules\Common\Database\DatabaseEventStore;
use App\Modules\Common\Database\DatabaseProcessedCommands;
use App\Modules\Common\Database\DatabaseTransaction;
use Common\Application\Clock;
use Common\Application\Command\ProcessedCommands;
use Common\Application\EventStore;
use Common\Application\Transaction;
use Illuminate\Support\ServiceProvider;

/**
 * The shared ports: event store, processed commands and transaction on the default database connection, so that one
 * transaction covers everything a command writes, and the clock of the machine. The module whose events are stored binds the EventSerializer.
 */
final class CommonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventStore::class, DatabaseEventStore::class);
        $this->app->singleton(ProcessedCommands::class, DatabaseProcessedCommands::class);
        $this->app->singleton(Transaction::class, DatabaseTransaction::class);
        $this->app->singleton(Clock::class, SystemClock::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
