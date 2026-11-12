<?php

declare(strict_types=1);

namespace Payroll\Domain;

use Payroll\Domain\Exception\InvalidIdentifier;

/**
 * The author of an adjustment. The id belongs to the user directory, so its format is not imposed here.
 * It only has to be a visible reference of reasonable length without control characters, because it is
 * stored in an append-only history and cannot be corrected later (ADR 0004).
 */
final readonly class SpecialistId
{
    public const int MAX_LENGTH = 64;

    public string $value;

    public function __construct(string $value)
    {
        $trimmed = mb_check_encoding($value, 'UTF-8') ? VisibleText::trim($value) : '';

        if ($trimmed === '' || preg_match('/\p{Cc}/u', $trimmed) === 1 || mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw InvalidIdentifier::invalidSpecialist(self::MAX_LENGTH);
        }

        $this->value = $trimmed;
    }
}
