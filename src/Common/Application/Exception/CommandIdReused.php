<?php

declare(strict_types=1);

namespace Common\Application\Exception;

/** The id of a command that was carried out came again with different content. Nothing was done. */
final class CommandIdReused extends \RuntimeException
{
    public static function withId(string $commandId): self
    {
        return new self(\sprintf('Command id %s was already used for a different request.', $commandId));
    }
}
