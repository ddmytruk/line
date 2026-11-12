<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Database;

use App\Modules\Common\Database\EventSerializer;
use App\Modules\Common\Database\SerializedEvent;
use Common\Domain\DomainEvent;
use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Exception\PayrollException;
use Payroll\Domain\Money;
use Payroll\Domain\SpecialistId;

/**
 * The events of an earning line as stored data (ADR 0014). The type names are fixed here and never derived from class
 * names, so a class can be renamed without touching stored events. Stored values go through the value objects of the
 * domain when an event is read back; a value the domain refuses is damaged data, not a mistake of the current request.
 */
final readonly class PayrollEventSerializer implements EventSerializer
{
    private const string CALCULATED = 'payroll.system_value_calculated';
    private const string RECALCULATED = 'payroll.system_value_recalculated';
    private const string RECALCULATION_IGNORED = 'payroll.system_recalculation_ignored';
    private const string ADJUSTMENT_ADDED = 'payroll.manual_adjustment_added';

    public function serialize(DomainEvent $event): SerializedEvent
    {
        return match (true) {
            $event instanceof SystemValueCalculated => new SerializedEvent(self::CALCULATED, self::value($event->lineId, $event->value, $event->occurredAt)),
            $event instanceof SystemValueRecalculated => new SerializedEvent(self::RECALCULATED, self::value($event->lineId, $event->value, $event->occurredAt)),
            $event instanceof SystemRecalculationIgnored => new SerializedEvent(self::RECALCULATION_IGNORED, self::value($event->lineId, $event->calculatedValue, $event->occurredAt)),
            $event instanceof ManualAdjustmentAdded => new SerializedEvent(self::ADJUSTMENT_ADDED, [
                'lineId' => $event->lineId->value,
                'number' => $event->number,
                'amount' => StoredValues::money($event->amount),
                'comment' => $event->comment->value,
                'specialistId' => $event->specialistId->value,
                'occurredAt' => StoredValues::time($event->occurredAt),
            ]),
            default => throw new \InvalidArgumentException(\sprintf('%s is not a Payroll event.', $event::class)),
        };
    }

    public function deserialize(SerializedEvent $event): DomainEvent
    {
        try {
            return self::event($event);
        } catch (PayrollException $refused) {
            throw new \UnexpectedValueException(\sprintf('A stored "%s" event breaks a rule of the domain.', $event->type), previous: $refused);
        }
    }

    private static function event(SerializedEvent $event): DomainEvent
    {
        $data = $event->payload;
        $lineId = new EarningLineId(StoredValues::string($data, 'lineId'));
        $occurredAt = StoredValues::toTime($data['occurredAt'] ?? null);

        return match ($event->type) {
            self::CALCULATED => new SystemValueCalculated($lineId, StoredValues::toMoney($data['value'] ?? null), $occurredAt),
            self::RECALCULATED => new SystemValueRecalculated($lineId, StoredValues::toMoney($data['value'] ?? null), $occurredAt),
            self::RECALCULATION_IGNORED => new SystemRecalculationIgnored($lineId, StoredValues::toMoney($data['value'] ?? null), $occurredAt),
            self::ADJUSTMENT_ADDED => new ManualAdjustmentAdded(
                $lineId,
                StoredValues::int($data, 'number'),
                StoredValues::toMoney($data['amount'] ?? null),
                new AdjustmentComment(StoredValues::string($data, 'comment')),
                new SpecialistId(StoredValues::string($data, 'specialistId')),
                $occurredAt,
            ),
            default => throw new \UnexpectedValueException(\sprintf('Unknown stored event type "%s".', $event->type)),
        };
    }

    /** @return array<string, mixed> */
    private static function value(EarningLineId $lineId, Money $value, \DateTimeImmutable $occurredAt): array
    {
        return ['lineId' => $lineId->value, 'value' => StoredValues::money($value), 'occurredAt' => StoredValues::time($occurredAt)];
    }
}
