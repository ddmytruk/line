<?php

declare(strict_types=1);

namespace App\Modules\Common\Database;

use Common\Application\Command\ProcessedCommands;
use Common\Application\Exception\CommandAlreadyProcessed;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/** Processed command ids in the `processed_commands` table; the primary key stops a second run of the same id. */
final readonly class DatabaseProcessedCommands implements ProcessedCommands
{
    private const string TABLE = 'processed_commands';

    public function __construct(private ConnectionInterface $connection) {}

    public function fingerprintOf(string $commandId): ?string
    {
        $fingerprint = $this->connection->table(self::TABLE)->where('command_id', $commandId)->value('fingerprint');
        \assert($fingerprint === null || \is_string($fingerprint));

        return $fingerprint;
    }

    public function add(string $commandId, string $fingerprint): void
    {
        try {
            $this->connection->table(self::TABLE)->insert(['command_id' => $commandId, 'fingerprint' => $fingerprint]);
        } catch (UniqueConstraintViolationException) {
            throw CommandAlreadyProcessed::withId($commandId);
        }
    }
}
