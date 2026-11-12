<?php

declare(strict_types=1);

namespace Payroll\Domain\Event;

use Common\Domain\DomainEvent;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Money;

/** The payroll calculation sent a new value for a frozen line. The line did not change; the value is kept for the audit and to show drift. */
final readonly class SystemRecalculationIgnored implements DomainEvent
{
    public function __construct(
        public EarningLineId $lineId,
        public Money $calculatedValue,
        public \DateTimeImmutable $occurredAt,
    ) {}

    public function aggregateId(): string
    {
        return $this->lineId->value;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
