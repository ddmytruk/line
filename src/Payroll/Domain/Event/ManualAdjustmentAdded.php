<?php

declare(strict_types=1);

namespace Payroll\Domain\Event;

use Common\Domain\DomainEvent;
use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Money;
use Payroll\Domain\SpecialistId;

/** A specialist added a manual adjustment. The first one freezes the line. Numbers run 1, 2, 3... within a line. */
final readonly class ManualAdjustmentAdded implements DomainEvent
{
    public function __construct(
        public EarningLineId $lineId,
        public int $number,
        public Money $amount,
        public AdjustmentComment $comment,
        public SpecialistId $specialistId,
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
