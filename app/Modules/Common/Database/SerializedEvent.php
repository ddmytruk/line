<?php

declare(strict_types=1);

namespace App\Modules\Common\Database;

/** An event as it is stored: the name of its type and its data. */
final readonly class SerializedEvent
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $type,
        public array $payload,
    ) {}
}
