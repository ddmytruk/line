<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Cli;

use Common\Domain\DomainEvent;
use Payroll\Domain\EarningLine;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;

/** Renders a line as text: the history a specialist reads, and the full log of recorded facts. */
final class HistoryView
{
    /** Frozen (or latest) system value, every adjustment, current value. */
    public static function history(EarningLine $line): string
    {
        $rows = [[$line->isFrozen() ? 'System value (frozen)' : 'System value', MoneyFormat::amount($line->systemValue()), '']];

        foreach ($line->adjustments() as $adjustment) {
            $rows[] = ['Adjustment ' . $adjustment->number, MoneyFormat::signed($adjustment->amount), $adjustment->comment->value];
        }

        $rows[] = ['Current value', MoneyFormat::amount($line->currentValue()), ''];

        return self::table(['Entry', 'Value', 'Comment'], $rows);
    }

    /** @param list<DomainEvent> $events */
    public static function log(array $events): string
    {
        $rows = array_map(static fn(DomainEvent $event): array => [
            $event->occurredAt()->format('Y-m-d H:i:s'),
            ...match (true) {
                $event instanceof SystemValueCalculated => ['Calculated', MoneyFormat::amount($event->value), ''],
                $event instanceof SystemValueRecalculated => ['Recalculated', MoneyFormat::amount($event->value), ''],
                $event instanceof SystemRecalculationIgnored => ['Recalculation ignored (line frozen)', MoneyFormat::amount($event->calculatedValue), ''],
                $event instanceof ManualAdjustmentAdded => [
                    \sprintf('Adjustment %d by %s', $event->number, $event->specialistId->value),
                    MoneyFormat::signed($event->amount),
                    $event->comment->value,
                ],
                default => [$event::class, '', ''],
            },
        ], $events);

        return self::table(['Time (UTC)', 'Fact', 'Value', 'Comment'], $rows);
    }

    /**
     * @param list<string>       $header
     * @param list<list<string>> $rows
     */
    private static function table(array $header, array $rows): string
    {
        $widths = array_map(
            static fn(int $column): int => max(array_map(static fn(array $row): int => mb_strlen($row[$column]), [$header, ...$rows])),
            array_keys($header),
        );
        $rule = array_map(static fn(int $width): string => str_repeat('-', $width), $widths);

        return implode("\n", array_map(
            static fn(array $cells): string => self::row($cells, $widths),
            [$header, $rule, ...$rows],
        )) . "\n";
    }

    /**
     * @param list<string> $cells
     * @param list<int>    $widths
     */
    private static function row(array $cells, array $widths): string
    {
        $padded = [];

        foreach ($cells as $column => $cell) {
            $padded[] = $cell . str_repeat(' ', $widths[$column] - mb_strlen($cell));
        }

        return rtrim(implode('  ', $padded));
    }
}
