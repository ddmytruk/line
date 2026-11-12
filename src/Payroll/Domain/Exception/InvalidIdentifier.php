<?php

declare(strict_types=1);

namespace Payroll\Domain\Exception;

final class InvalidIdentifier extends PayrollException
{
    public static function notUuid(): self
    {
        return new self('An earning line id must be a UUID.');
    }

    public static function invalidSpecialist(int $maxLength): self
    {
        return new self(\sprintf('A specialist id must be 1 to %d visible characters without control characters.', $maxLength));
    }
}
