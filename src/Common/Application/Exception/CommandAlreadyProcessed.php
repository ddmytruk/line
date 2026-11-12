<?php

declare(strict_types=1);

namespace Common\Application\Exception;

/** Another run of the same command was recorded first, after this run checked that it was new. */
final class CommandAlreadyProcessed extends \RuntimeException
{
    public static function withId(string $commandId): self
    {
        return new self(\sprintf('Command %s has already been carried out.', $commandId));
    }
}
