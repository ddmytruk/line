<?php

declare(strict_types=1);

namespace Payroll\Domain\Exception;

final class InvalidAdjustmentComment extends PayrollException
{
    public static function blank(): self
    {
        return new self('A manual adjustment requires a comment explaining it.');
    }

    public static function notText(): self
    {
        return new self('A comment must be valid UTF-8 text without control characters.');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(\sprintf('A comment must not exceed %d characters.', $maxLength));
    }
}
