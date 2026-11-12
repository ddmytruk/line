<?php

declare(strict_types=1);

namespace Payroll\Application\History;

use Payroll\Domain\Money;

/** One fact in the history of a line, as a specialist reads it. Each kind of fact has its own constructor with only its own data. */
final readonly class HistoryEntry
{
    /**
     * @param Money  $amount the value the calculation sent, or the signed amount of an adjustment
     * @param ?Money $drift  only for an ignored recalculation: the value sent minus the frozen system value
     */
    private function __construct(
        public HistoryEntryKind $kind,
        public \DateTimeImmutable $at,
        public Money $amount,
        public Money $currentValueAfter,
        public ?int $adjustmentNumber = null,
        public ?string $comment = null,
        public ?string $specialistId = null,
        public ?Money $drift = null,
    ) {}

    public static function calculated(\DateTimeImmutable $at, Money $value): self
    {
        return new self(HistoryEntryKind::Calculated, $at, amount: $value, currentValueAfter: $value);
    }

    public static function recalculated(\DateTimeImmutable $at, Money $value): self
    {
        return new self(HistoryEntryKind::Recalculated, $at, amount: $value, currentValueAfter: $value);
    }

    public static function recalculationIgnored(\DateTimeImmutable $at, Money $value, Money $currentValue, Money $drift): self
    {
        return new self(HistoryEntryKind::RecalculationIgnored, $at, amount: $value, currentValueAfter: $currentValue, drift: $drift);
    }

    public static function adjusted(
        \DateTimeImmutable $at,
        Money $amount,
        Money $currentValueAfter,
        int $number,
        string $comment,
        string $specialistId,
    ): self {
        return new self(
            HistoryEntryKind::Adjusted,
            $at,
            amount: $amount,
            currentValueAfter: $currentValueAfter,
            adjustmentNumber: $number,
            comment: $comment,
            specialistId: $specialistId,
        );
    }
}
