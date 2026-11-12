<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use Common\Application\Command\SafeToRetryOnConflict;

/** Like TakeNote, but safe to run again after a concurrency conflict. */
final readonly class TakeNoteSafely implements SafeToRetryOnConflict
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
