<?php

declare(strict_types=1);

namespace Common\Application\Command;

use Common\Application\Exception\CommandAlreadyProcessed;

/** Remembers which commands were carried out, by id, with a fingerprint of their content. */
interface ProcessedCommands
{
    /** @return ?string the fingerprint stored with the id, or null if no command with this id was carried out */
    public function fingerprintOf(string $commandId): ?string;

    /** @throws CommandAlreadyProcessed if the id is already stored; nothing is changed */
    public function add(string $commandId, string $fingerprint): void;
}
