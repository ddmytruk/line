<?php

declare(strict_types=1);

namespace Common\Application\Exception;

/** The stream changed after the writer read it. Nothing was written; the writer has to read the new state and decide again. */
final class ConcurrencyConflict extends \RuntimeException
{
    public static function in(string $streamId, int $expectedVersion, int $actualVersion): self
    {
        return new self(\sprintf(
            'Stream "%s" is at version %d, but the change was based on version %d.',
            $streamId,
            $actualVersion,
            $expectedVersion,
        ));
    }
}
