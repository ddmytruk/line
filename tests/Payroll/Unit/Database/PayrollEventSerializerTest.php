<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Database;

use App\Modules\Common\Database\SerializedEvent;
use App\Modules\Payroll\Database\PayrollEventSerializer;
use App\Modules\Payroll\Database\StoredValues;
use Common\Domain\DomainEvent;
use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Exception\InvalidMoneyAmount;
use Payroll\Domain\SpecialistId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\SomethingHappened;
use Tests\Support\Payroll\Lines;

/** Payroll events are stored under fixed names and read back unchanged; damaged stored data is refused. */
#[CoversClass(PayrollEventSerializer::class)]
#[CoversClass(StoredValues::class)]
final class PayrollEventSerializerTest extends TestCase
{
    /** @return iterable<string, array{DomainEvent, string}> */
    public static function events(): iterable
    {
        $at = Lines::at('2026-01-15 09:00:00.123456');

        yield 'calculated' => [new SystemValueCalculated(Lines::id(), Lines::usd('1000.00'), $at), 'payroll.system_value_calculated'];
        yield 'recalculated' => [new SystemValueRecalculated(Lines::id(), Lines::usd('1050.00'), $at), 'payroll.system_value_recalculated'];
        yield 'recalculation ignored' => [new SystemRecalculationIgnored(Lines::id(), Lines::usd('1100.00'), $at), 'payroll.system_recalculation_ignored'];
        yield 'adjustment added' => [
            new ManualAdjustmentAdded(Lines::id(), 1, Lines::usd('-45.55'), new AdjustmentComment('Dental plan waived'), new SpecialistId('specialist-1'), $at),
            'payroll.manual_adjustment_added',
        ];
    }

    #[DataProvider('events')]
    public function testStoresAnEventUnderAFixedNameAndReadsItBackUnchanged(DomainEvent $event, string $type): void
    {
        $serializer = new PayrollEventSerializer();
        $serialized = $serializer->serialize($event);
        $stored = json_decode(json_encode($serialized->payload, \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR);
        \assert(\is_array($stored));
        /** @var array<string, mixed> $stored */
        $read = $serializer->deserialize(new SerializedEvent($serialized->type, $stored));

        self::assertSame($type, $serialized->type);
        self::assertEquals($event, $read);
        self::assertSame($event->occurredAt()->format('Y-m-d H:i:s.u e'), $read->occurredAt()->format('Y-m-d H:i:s.u e'));
    }

    public function testKeepsAmountsAsExactDecimalStringsAndTimesInUtc(): void
    {
        $at = new \DateTimeImmutable('2026-01-15 11:00:00.5', new \DateTimeZone('Europe/Kyiv'));
        $payload = new PayrollEventSerializer()->serialize(new SystemValueCalculated(Lines::id(), Lines::usd('1104.10'), $at))->payload;

        self::assertSame(['amount' => '1104.10', 'currency' => 'USD'], $payload['value']);
        self::assertSame('2026-01-15T09:00:00.500000Z', $payload['occurredAt']);
    }

    /** @return iterable<string, array{SerializedEvent, class-string<\Throwable>}> */
    public static function damagedEvents(): iterable
    {
        $value = ['lineId' => Lines::LINE_ID, 'value' => ['amount' => '10.00', 'currency' => 'USD'], 'occurredAt' => '2026-01-15T09:00:00.000000Z'];

        yield 'unknown type' => [new SerializedEvent('payroll.unknown', $value), \UnexpectedValueException::class];
        yield 'missing amount' => [new SerializedEvent('payroll.system_value_calculated', ['value' => null] + $value), \UnexpectedValueException::class];
        yield 'unknown currency' => [new SerializedEvent('payroll.system_value_calculated', ['value' => ['amount' => '10.00', 'currency' => 'XXX']] + $value), \UnexpectedValueException::class];
        yield 'time with a zone' => [new SerializedEvent('payroll.system_value_calculated', ['occurredAt' => '2026-01-15T09:00:00+02:00'] + $value), \UnexpectedValueException::class];
        yield 'number as text' => [new SerializedEvent('payroll.manual_adjustment_added', $value + ['number' => '1', 'amount' => ['amount' => '1.00', 'currency' => 'USD'], 'comment' => 'c', 'specialistId' => 's']), \UnexpectedValueException::class];
        yield 'line id the domain refuses' => [new SerializedEvent('payroll.system_value_calculated', ['lineId' => 'not-a-uuid'] + $value), \UnexpectedValueException::class];
    }

    public function testAStoredValueTheDomainRefusesIsDamagedDataNotARefusalOfARequest(): void
    {
        $event = new SerializedEvent('payroll.system_value_calculated', [
            'lineId' => Lines::LINE_ID,
            'value' => ['amount' => '10.005', 'currency' => 'USD'],
            'occurredAt' => '2026-01-15T09:00:00.000000Z',
        ]);

        try {
            new PayrollEventSerializer()->deserialize($event);
            self::fail('A stored amount the domain refuses must be reported as damaged data.');
        } catch (\UnexpectedValueException $damaged) {
            self::assertInstanceOf(InvalidMoneyAmount::class, $damaged->getPrevious());
        }
    }

    /** @param class-string<\Throwable> $failure */
    #[DataProvider('damagedEvents')]
    public function testRefusesDamagedStoredData(SerializedEvent $event, string $failure): void
    {
        $this->expectException($failure);
        new PayrollEventSerializer()->deserialize($event);
    }

    public function testRefusesAnEventOfAnotherModule(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PayrollEventSerializer()->serialize(new SomethingHappened('a', 'b'));
    }
}
