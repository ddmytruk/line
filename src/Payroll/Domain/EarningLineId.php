<?php

declare(strict_types=1);

namespace Payroll\Domain;

use Payroll\Domain\Exception\InvalidIdentifier;

/** Identity of a line: a UUID in canonical lower case, so one line never has two spellings (ADR 0004). */
final readonly class EarningLineId
{
    private const string UUID_PATTERN = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/';

    public string $value;

    public function __construct(string $value)
    {
        $canonical = strtolower($value);

        if (preg_match(self::UUID_PATTERN, $canonical) !== 1) {
            throw InvalidIdentifier::notUuid();
        }

        $this->value = $canonical;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
