<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Cli;

use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\History\HistoryEntry;
use Payroll\Application\History\HistoryEntryKind;

/** Renders the history of a line as text: the table a specialist reads, and the full log of recorded facts. */
final class HistoryView
{
    /** Frozen (or latest) system value, every adjustment, current value, and the drift of a frozen line. */
    public static function history(EarningLineHistory $line): string
    {
        $rows = [[$line->frozen ? 'System value (frozen)' : 'System value', MoneyFormat::amount($line->systemValue), '']];

        foreach ($line->adjustments() as $adjustment) {
            $rows[] = ['Adjustment ' . $adjustment->adjustmentNumber, MoneyFormat::signed($adjustment->amount), (string) $adjustment->comment];
        }

        $rows[] = ['Current value', MoneyFormat::amount($line->currentValue), ''];

        if ($line->frozen) {
            $rows[] = ['Drift', MoneyFormat::signed($line->drift()), 'latest calculation ' . MoneyFormat::amount($line->lastCalculatedValue) . ', not applied'];
        }

        return self::table(['Entry', 'Value', 'Comment'], $rows);
    }

    /** Every recorded fact in order, with the value of the line after it. */
    public static function log(EarningLineHistory $line): string
    {
        $rows = array_map(static fn(HistoryEntry $entry): array => [
            $entry->at->format('Y-m-d H:i:s'),
            match ($entry->kind) {
                HistoryEntryKind::Calculated => 'Calculated',
                HistoryEntryKind::Recalculated => 'Recalculated',
                HistoryEntryKind::RecalculationIgnored => \sprintf('Recalculation ignored, drift %s', MoneyFormat::signed($entry->drift ?? throw new \LogicException('An ignored recalculation has no drift.'))),
                HistoryEntryKind::Adjusted => \sprintf('Adjustment %d by %s', $entry->adjustmentNumber, $entry->specialistId),
            },
            $entry->kind === HistoryEntryKind::Adjusted ? MoneyFormat::signed($entry->amount) : MoneyFormat::amount($entry->amount),
            MoneyFormat::amount($entry->currentValueAfter),
            (string) $entry->comment,
        ], $line->entries);

        return self::table(['Time (UTC)', 'Fact', 'Value', 'Line after', 'Comment'], $rows);
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
