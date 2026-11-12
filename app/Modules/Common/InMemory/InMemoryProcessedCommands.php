<?php

declare(strict_types=1);

namespace App\Modules\Common\InMemory;

use Common\Application\Command\ProcessedCommands;
use Common\Application\Exception\CommandAlreadyProcessed;

/** Processed command ids in the memory of the running process. */
final class InMemoryProcessedCommands implements ProcessedCommands, InMemoryState
{
    /** @var array<string, string> */
    private array $fingerprints = [];

    public function fingerprintOf(string $commandId): ?string
    {
        return $this->fingerprints[$commandId] ?? null;
    }

    public function add(string $commandId, string $fingerprint): void
    {
        if (isset($this->fingerprints[$commandId])) {
            throw CommandAlreadyProcessed::withId($commandId);
        }

        $this->fingerprints[$commandId] = $fingerprint;
    }

    /** @return array<string, string> */
    public function snapshot(): array
    {
        return $this->fingerprints;
    }

    public function restore(mixed $snapshot): void
    {
        \assert(\is_array($snapshot));
        /** @var array<string, string> $snapshot */
        $this->fingerprints = $snapshot;
    }
}
