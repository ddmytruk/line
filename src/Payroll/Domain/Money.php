<?php

declare(strict_types=1);

namespace Payroll\Domain;

use Payroll\Domain\Exception\CurrencyMismatch;
use Payroll\Domain\Exception\InvalidMoneyAmount;

/**
 * An amount in integer minor units (cents) and its currency (ADR 0003).
 *
 * Amounts are parsed from strings and never pass through a float, so 0.10 + 0.20 is exactly 0.30.
 * A fraction of a cent is rejected rather than rounded: an inexact amount must not enter the history.
 */
final readonly class Money
{
    /** ±(10^15 − 1) cents: far above any pay line, and sums of two amounts cannot overflow an integer. */
    public const int MAX_MINOR_UNITS = 999_999_999_999_999;

    private const string DECIMAL_PATTERN = '/\A([+-])?(\d{1,13})(?:\.(\d{1,2}))?\z/';

    private function __construct(
        public int $minorUnits,
        public Currency $currency,
    ) {
        if (abs($minorUnits) > self::MAX_MINOR_UNITS) {
            throw InvalidMoneyAmount::outOfRange();
        }
    }

    public static function ofMinor(int $minorUnits, Currency $currency): self
    {
        return new self($minorUnits, $currency);
    }

    /** Exact decimal amounts only: "1050", "1050.5", "-45.55", "+100.10". */
    public static function fromDecimal(string $amount, Currency $currency): self
    {
        if (preg_match(self::DECIMAL_PATTERN, trim($amount), $parts) !== 1) {
            throw InvalidMoneyAmount::notExact();
        }

        $minorUnits = (int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0');

        return new self($parts[1] === '-' ? -$minorUnits : $minorUnits, $currency);
    }

    /** @throws InvalidMoneyAmount if the sum is out of range */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    /** @throws InvalidMoneyAmount if the difference is out of range */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minorUnits === $other->minorUnits;
    }

    /** "-45.55", "1050.00" */
    public function toDecimalString(): string
    {
        $absolute = abs($this->minorUnits);

        return \sprintf('%s%d.%02d', $this->minorUnits < 0 ? '-' : '', intdiv($absolute, 100), $absolute % 100);
    }

    /** @throws CurrencyMismatch */
    public function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }
    }
}
