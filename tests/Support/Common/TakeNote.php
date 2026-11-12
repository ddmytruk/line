<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use Common\Application\Command\Command;

/** A minimal command for testing the command bus without business rules. */
final readonly class TakeNote implements Command
{
    public function __construct(
        public string $commandId,
        public string $text,
    ) {}

    public function commandId(): string
    {
        return $this->commandId;
    }
}
