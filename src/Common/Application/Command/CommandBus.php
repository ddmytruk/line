<?php

declare(strict_types=1);

namespace Common\Application\Command;

use Common\Application\Exception\CommandAlreadyProcessed;
use Common\Application\Exception\CommandIdReused;
use Common\Application\Exception\ConcurrencyConflict;
use Common\Application\Transaction;

/**
 * Carries out commands: runs the handler of a command in a transaction, at most once per command id (ADR 0009).
 *
 * A repeated command with the same content does nothing. A repeated id with other content is refused. If the handler
 * fails, nothing it wrote is kept and the command is not marked as carried out, so it can be sent again.
 */
final readonly class CommandBus
{
    /** @param array<class-string<Command>, callable(Command): void> $handlers */
    public function __construct(
        private Transaction $transaction,
        private ProcessedCommands $processed,
        private array $handlers,
    ) {}

    /**
     * @throws CommandIdReused         if the id was used for a command with other content
     * @throws ConcurrencyConflict     if the state changed while the command was carried out, after the one retry of a safe command
     * @throws CommandAlreadyProcessed if a copy of a safe command was carried out during its retry; the command was then carried out once
     */
    public function dispatch(Command $command): void
    {
        $handler = $this->handlers[$command::class] ?? throw new \LogicException(\sprintf('No handler for %s.', $command::class));

        try {
            $this->runOnce($command, $handler);
        } catch (ConcurrencyConflict|CommandAlreadyProcessed $conflict) {
            // A second run sees the new state: an already processed command is skipped, a safe command decides again.
            if ($conflict instanceof ConcurrencyConflict && !$command instanceof SafeToRetryOnConflict) {
                throw $conflict;
            }

            $this->runOnce($command, $handler);
        }
    }

    /** @param callable(Command): void $handler */
    private function runOnce(Command $command, callable $handler): void
    {
        $this->transaction->run(function () use ($command, $handler): void {
            $fingerprint = hash('sha256', serialize($command));
            $known = $this->processed->fingerprintOf($command->commandId());

            if ($known !== null) {
                if ($known !== $fingerprint) {
                    throw CommandIdReused::withId($command->commandId());
                }

                return;
            }

            $handler($command);
            $this->processed->add($command->commandId(), $fingerprint);
        });
    }
}
