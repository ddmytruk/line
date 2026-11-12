<?php

declare(strict_types=1);

namespace App\Modules\Common\Database;

use Common\Application\Transaction;
use Illuminate\Database\ConnectionInterface;

/**
 * A database transaction. Used inside another run, it becomes a savepoint: a failure undoes only the inner work.
 * A failed run is not repeated here; repeating a command is the decision of the command bus.
 */
final readonly class DatabaseTransaction implements Transaction
{
    public function __construct(private ConnectionInterface $connection) {}

    public function run(callable $work): mixed
    {
        return $this->connection->transaction(static fn(): mixed => $work());
    }
}
