<?php

declare(strict_types=1);

namespace Payroll\Domain;

use Payroll\Domain\Exception\InvalidAdjustmentComment;

/**
 * The mandatory explanation of a manual adjustment.
 * The 500-character limit is an assumption (open question Q9).
 */
final readonly class AdjustmentComment
{
    public const int MAX_LENGTH = 500;

    public string $value;

    public function __construct(string $value)
    {
        // Control characters other than tabs and line breaks would corrupt how the history is displayed.
        if (!mb_check_encoding($value, 'UTF-8') || preg_match('/[^\P{Cc}\t\n\r]/u', $value) === 1) {
            throw InvalidAdjustmentComment::notText();
        }

        $trimmed = VisibleText::trim($value);

        if ($trimmed === '') {
            throw InvalidAdjustmentComment::blank();
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw InvalidAdjustmentComment::tooLong(self::MAX_LENGTH);
        }

        $this->value = $trimmed;
    }
}
