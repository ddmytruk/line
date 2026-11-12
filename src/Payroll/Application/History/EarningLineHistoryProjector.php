<?php

declare(strict_types=1);

namespace Payroll\Application\History;

use Common\Application\ReadModel\Projector;
use Common\Domain\DomainEvent;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;

/**
 * Builds the history of a line from its events. It only reads what the events say; the rules were applied when they were recorded.
 * An event that does not fit the stored history means the read model is out of step with the events; the cure is a rebuild.
 */
final readonly class EarningLineHistoryProjector implements Projector
{
    public function __construct(private EarningLineHistories $histories) {}

    public function project(DomainEvent $event): void
    {
        match (true) {
            $event instanceof SystemValueCalculated => $this->whenSystemValueCalculated($event),
            $event instanceof SystemValueRecalculated => $this->whenSystemValueRecalculated($event),
            $event instanceof SystemRecalculationIgnored => $this->whenSystemRecalculationIgnored($event),
            $event instanceof ManualAdjustmentAdded => $this->whenManualAdjustmentAdded($event),
            default => null,
        };
    }

    public function reset(): void
    {
        $this->histories->clear();
    }

    private function whenSystemValueCalculated(SystemValueCalculated $event): void
    {
        $lineId = $event->aggregateId();

        if ($this->histories->find($lineId) !== null) {
            throw new \LogicException(\sprintf('Line %s already has a history; rebuild the read models.', $lineId));
        }

        $this->histories->save(EarningLineHistory::start($lineId, $event->occurredAt, $event->value));
    }

    private function whenSystemValueRecalculated(SystemValueRecalculated $event): void
    {
        $this->histories->save($this->historyOf($event)->recalculated($event->occurredAt, $event->value));
    }

    private function whenSystemRecalculationIgnored(SystemRecalculationIgnored $event): void
    {
        $this->histories->save($this->historyOf($event)->recalculationIgnored($event->occurredAt, $event->calculatedValue));
    }

    private function whenManualAdjustmentAdded(ManualAdjustmentAdded $event): void
    {
        $this->histories->save($this->historyOf($event)->adjusted(
            $event->occurredAt,
            $event->amount,
            $event->number,
            $event->comment->value,
            $event->specialistId->value,
        ));
    }

    private function historyOf(DomainEvent $event): EarningLineHistory
    {
        return $this->histories->find($event->aggregateId())
            ?? throw new \LogicException(\sprintf('The history of line %s is missing; rebuild the read models.', $event->aggregateId()));
    }
}
