<?php

declare(strict_types=1);

namespace Payroll\Application\History;

use Payroll\Domain\Money;

/**
 * The current value of a line and every fact that produced it, built for reading (ADR 0010).
 *
 * The version is the one a specialist sends back with an adjustment, so the adjustment is refused if the line changed meanwhile.
 * Each recorded fact gives the next history through a method of its own; a history is never changed in place.
 */
final readonly class EarningLineHistory
{
    /** @param list<HistoryEntry> $entries in the order they happened */
    public function __construct(
        public string $lineId,
        public int $version,
        public Money $systemValue,
        public Money $lastCalculatedValue,
        public Money $currentValue,
        public bool $frozen,
        public array $entries,
    ) {}

    /** The history of a line the calculation has just created. */
    public static function start(string $lineId, \DateTimeImmutable $at, Money $value): self
    {
        return new self(
            lineId: $lineId,
            version: 1,
            systemValue: $value,
            lastCalculatedValue: $value,
            currentValue: $value,
            frozen: false,
            entries: [HistoryEntry::calculated($at, $value)],
        );
    }

    /** The calculation replaced the value of an open line. */
    public function recalculated(\DateTimeImmutable $at, Money $value): self
    {
        return $this->next(
            HistoryEntry::recalculated($at, $value),
            systemValue: $value,
            lastCalculatedValue: $value,
            currentValue: $value,
            frozen: false,
        );
    }

    /** The calculation sent a value the frozen line did not take: only the latest calculated value moves. */
    public function recalculationIgnored(\DateTimeImmutable $at, Money $value): self
    {
        return $this->next(
            HistoryEntry::recalculationIgnored($at, $value, $this->currentValue, $value->subtract($this->systemValue)),
            lastCalculatedValue: $value,
            frozen: true,
        );
    }

    /** A specialist adjusted the line by a signed amount; the line is frozen from now on. */
    public function adjusted(\DateTimeImmutable $at, Money $amount, int $number, string $comment, string $specialistId): self
    {
        $currentValue = $this->currentValue->add($amount);

        return $this->next(
            HistoryEntry::adjusted($at, $amount, $currentValue, $number, $comment, $specialistId),
            currentValue: $currentValue,
            frozen: true,
        );
    }

    /** How far the latest calculated value is from the frozen system value; zero while the line is open (ADR 0011). */
    public function drift(): Money
    {
        return $this->lastCalculatedValue->subtract($this->systemValue);
    }

    /** @return list<HistoryEntry> */
    public function adjustments(): array
    {
        return array_values(array_filter($this->entries, static fn(HistoryEntry $entry): bool => $entry->kind === HistoryEntryKind::Adjusted));
    }

    /** The next version of the history: one more entry, and the values that the fact changed. */
    private function next(
        HistoryEntry $entry,
        ?Money $systemValue = null,
        ?Money $lastCalculatedValue = null,
        ?Money $currentValue = null,
        ?bool $frozen = null,
    ): self {
        return new self(
            lineId: $this->lineId,
            version: $this->version + 1,
            systemValue: $systemValue ?? $this->systemValue,
            lastCalculatedValue: $lastCalculatedValue ?? $this->lastCalculatedValue,
            currentValue: $currentValue ?? $this->currentValue,
            frozen: $frozen ?? $this->frozen,
            entries: [...$this->entries, $entry],
        );
    }
}
