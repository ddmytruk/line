<?php

declare(strict_types=1);

namespace Payroll\Domain;

use Common\Domain\AggregateRoot;
use Common\Domain\DomainEvent;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Exception\ZeroAdjustment;

/**
 * One line of an employee's pay: a value calculated by the system that a specialist may adjust by hand.
 *
 * Invariants:
 *  - an adjustment is a non-zero amount in the line's currency with a comment;
 *  - adjustments are only appended, there is no way to edit or remove one;
 *  - the first adjustment freezes the system value for good, even if the adjustments later add up to zero;
 *  - current value = system value + sum of adjustments.
 *
 * A recalculation of a frozen line does not change it, but is recorded so the history keeps
 * everything the calculation sent (ADR 0006).
 */
final class EarningLine extends AggregateRoot
{
    private EarningLineId $id;

    /** Latest value from the calculation while open; the frozen value once adjusted. */
    private Money $systemValue;

    /** Latest value the calculation sent. Differs from the system value only for a frozen line. */
    private Money $lastCalculatedValue;

    private Money $currentValue;

    /** @var list<ManualAdjustment> */
    private array $adjustments = [];

    public static function calculate(EarningLineId $id, Money $systemValue, \DateTimeImmutable $at): self
    {
        $line = new self();
        $line->recordThat(new SystemValueCalculated($id, $systemValue, self::utc($at)));

        return $line;
    }

    /**
     * A value from the calculation. It replaces the system value of an open line and is only
     * recorded for a frozen one. A value equal to the last one sent changes nothing and is not recorded.
     */
    public function recordSystemValue(Money $value, \DateTimeImmutable $at): void
    {
        $this->systemValue->assertSameCurrency($value);

        if ($value->equals($this->lastCalculatedValue)) {
            return;
        }

        if ($this->isFrozen()) {
            // Fails on an out-of-range drift before anything is recorded, so a stored value can always be compared.
            $value->subtract($this->systemValue);
        }

        $this->recordThat($this->isFrozen()
            ? new SystemRecalculationIgnored($this->id, $value, self::utc($at))
            : new SystemValueRecalculated($this->id, $value, self::utc($at)));
    }

    public function addManualAdjustment(
        Money $amount,
        AdjustmentComment $comment,
        SpecialistId $specialist,
        \DateTimeImmutable $at,
    ): void {
        $this->systemValue->assertSameCurrency($amount);

        if ($amount->isZero()) {
            throw ZeroAdjustment::notAllowed();
        }

        // Rules are decided here, before recording; apply() repeats the checks only to refuse a corrupted history.
        $this->currentValue->add($amount);

        $this->recordThat(new ManualAdjustmentAdded(
            $this->id,
            \count($this->adjustments) + 1,
            $amount,
            $comment,
            $specialist,
            self::utc($at),
        ));
    }

    public function aggregateId(): string
    {
        return $this->id->value;
    }

    public function id(): EarningLineId
    {
        return $this->id;
    }

    /** The system value the line is based on: the latest one while open, the frozen one after the first adjustment. */
    public function systemValue(): Money
    {
        return $this->systemValue;
    }

    public function currentValue(): Money
    {
        return $this->currentValue;
    }

    /** Freezing is permanent, even if the adjustments add up to zero. */
    public function isFrozen(): bool
    {
        return $this->adjustments !== [];
    }

    /** @return list<ManualAdjustment> in the order they were added */
    public function adjustments(): array
    {
        return $this->adjustments;
    }

    /** A history that contradicts the rules is corrupted: failing is safer than rebuilding a line with different money. */
    protected function apply(DomainEvent $event): void
    {
        if (isset($this->id) === $event instanceof SystemValueCalculated) {
            throw new \UnexpectedValueException('A line history must start with exactly one SystemValueCalculated.');
        }

        match (true) {
            $event instanceof SystemValueCalculated => $this->applySystemValueCalculated($event),
            $event instanceof SystemValueRecalculated => $this->applySystemValueRecalculated($event),
            $event instanceof SystemRecalculationIgnored => $this->applySystemRecalculationIgnored($event),
            $event instanceof ManualAdjustmentAdded => $this->applyManualAdjustmentAdded($event),
            default => throw new \UnexpectedValueException(\sprintf('An earning line cannot apply %s.', $event::class)),
        };
    }

    private function applySystemValueCalculated(SystemValueCalculated $event): void
    {
        $this->id = $event->lineId;
        $this->systemValue = $event->value;
        $this->lastCalculatedValue = $event->value;
        $this->currentValue = $event->value;
    }

    private function applySystemValueRecalculated(SystemValueRecalculated $event): void
    {
        if ($this->isFrozen()) {
            throw new \UnexpectedValueException('A frozen line cannot be recalculated.');
        }

        $this->systemValue->assertSameCurrency($event->value);
        $this->systemValue = $event->value;
        $this->lastCalculatedValue = $event->value;
        $this->currentValue = $event->value;
    }

    /** The value of the line does not change: only the value the calculation sent is remembered. */
    private function applySystemRecalculationIgnored(SystemRecalculationIgnored $event): void
    {
        if (!$this->isFrozen()) {
            throw new \UnexpectedValueException('Only a frozen line ignores a recalculation.');
        }

        $this->systemValue->assertSameCurrency($event->calculatedValue);
        $this->lastCalculatedValue = $event->calculatedValue;
    }

    private function applyManualAdjustmentAdded(ManualAdjustmentAdded $event): void
    {
        if ($event->number !== \count($this->adjustments) + 1) {
            throw new \UnexpectedValueException(\sprintf('Adjustment #%d is out of sequence.', $event->number));
        }

        $this->currentValue = $this->currentValue->add($event->amount);
        $this->adjustments[] = new ManualAdjustment(
            $event->number,
            $event->amount,
            $event->comment,
            $event->specialistId,
            $event->occurredAt,
        );
    }

    private static function utc(\DateTimeImmutable $at): \DateTimeImmutable
    {
        return $at->setTimezone(new \DateTimeZone('UTC'));
    }
}
