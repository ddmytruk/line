<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Database;

use Illuminate\Database\ConnectionInterface;
use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\History\HistoryEntry;
use Payroll\Application\History\HistoryEntryKind;
use Payroll\Domain\Exception\PayrollException;

/**
 * Line histories in the `earning_line_histories` table: the id of a line and its whole history as one JSON document,
 * because a history is only read whole, by line id (ADR 0014). The document can always be built again from the events.
 */
final readonly class DatabaseEarningLineHistories implements EarningLineHistories
{
    private const string TABLE = 'earning_line_histories';

    public function __construct(private ConnectionInterface $connection) {}

    public function find(string $lineId): ?EarningLineHistory
    {
        $document = $this->connection->table(self::TABLE)->where('line_id', $lineId)->value('history');

        if ($document === null) {
            return null;
        }

        \assert(\is_string($document));
        $data = json_decode($document, true, flags: \JSON_THROW_ON_ERROR);

        if (!\is_array($data)) {
            throw new \UnexpectedValueException(\sprintf('The stored history of line "%s" is not an object.', $lineId));
        }

        try {
            return self::toHistory($data);
        } catch (PayrollException $refused) {
            throw new \UnexpectedValueException(\sprintf('The stored history of line "%s" breaks a rule of the domain.', $lineId), previous: $refused);
        }
    }

    public function save(EarningLineHistory $history): void
    {
        $this->connection->table(self::TABLE)->upsert(
            [['line_id' => $history->lineId, 'history' => json_encode(self::document($history), \JSON_THROW_ON_ERROR)]],
            ['line_id'],
            ['history'],
        );
    }

    public function clear(): void
    {
        // DELETE, not TRUNCATE: TRUNCATE ends the transaction, and a rebuild clears and fills the table in one.
        $this->connection->table(self::TABLE)->delete();
    }

    /** @return array<string, mixed> */
    private static function document(EarningLineHistory $history): array
    {
        return [
            'lineId' => $history->lineId,
            'version' => $history->version,
            'systemValue' => StoredValues::money($history->systemValue),
            'lastCalculatedValue' => StoredValues::money($history->lastCalculatedValue),
            'currentValue' => StoredValues::money($history->currentValue),
            'frozen' => $history->frozen,
            'entries' => array_map(static fn(HistoryEntry $entry): array => [
                'kind' => $entry->kind->value,
                'at' => StoredValues::time($entry->at),
                'amount' => StoredValues::money($entry->amount),
                'currentValueAfter' => StoredValues::money($entry->currentValueAfter),
                'adjustmentNumber' => $entry->adjustmentNumber,
                'comment' => $entry->comment,
                'specialistId' => $entry->specialistId,
                'drift' => $entry->drift === null ? null : StoredValues::money($entry->drift),
            ], $history->entries),
        ];
    }

    /** @param array<mixed> $data */
    private static function toHistory(array $data): EarningLineHistory
    {
        if (!\is_bool($data['frozen'] ?? null) || !\is_array($data['entries'] ?? null)) {
            throw new \UnexpectedValueException('A stored history needs "frozen" and "entries".');
        }

        return new EarningLineHistory(
            lineId: StoredValues::string($data, 'lineId'),
            version: StoredValues::int($data, 'version'),
            systemValue: StoredValues::toMoney($data['systemValue'] ?? null),
            lastCalculatedValue: StoredValues::toMoney($data['lastCalculatedValue'] ?? null),
            currentValue: StoredValues::toMoney($data['currentValue'] ?? null),
            frozen: $data['frozen'],
            entries: array_values(array_map(self::toEntry(...), $data['entries'])),
        );
    }

    private static function toEntry(mixed $data): HistoryEntry
    {
        if (!\is_array($data)) {
            throw new \UnexpectedValueException('A stored history entry must be an object.');
        }

        $at = StoredValues::toTime($data['at'] ?? null);
        $amount = StoredValues::toMoney($data['amount'] ?? null);
        $after = StoredValues::toMoney($data['currentValueAfter'] ?? null);

        return match (HistoryEntryKind::tryFrom(StoredValues::string($data, 'kind'))) {
            HistoryEntryKind::Calculated => HistoryEntry::calculated($at, $amount),
            HistoryEntryKind::Recalculated => HistoryEntry::recalculated($at, $amount),
            HistoryEntryKind::RecalculationIgnored => HistoryEntry::recalculationIgnored($at, $amount, $after, StoredValues::toMoney($data['drift'] ?? null)),
            HistoryEntryKind::Adjusted => HistoryEntry::adjusted(
                $at,
                $amount,
                $after,
                StoredValues::int($data, 'adjustmentNumber'),
                StoredValues::string($data, 'comment'),
                StoredValues::string($data, 'specialistId'),
            ),
            null => throw new \UnexpectedValueException('Unknown kind of a stored history entry.'),
        };
    }
}
