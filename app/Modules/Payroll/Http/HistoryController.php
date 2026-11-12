<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

use Illuminate\Http\JsonResponse;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\History\HistoryEntry;
use Payroll\Application\Query\GetEarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistoryHandler;
use Payroll\Domain\Money;
use Symfony\Component\HttpFoundation\Response;

/** GET /lines/{lineId}/history: what a specialist reads, including the version to send back with an adjustment. */
final readonly class HistoryController
{
    public function __construct(private GetEarningLineHistoryHandler $getHistory) {}

    public function __invoke(string $lineId): Response
    {
        return Responses::of(fn(): Response => new JsonResponse(self::json(($this->getHistory)(new GetEarningLineHistory($lineId)))));
    }

    /** @return array<string, mixed> */
    private static function json(EarningLineHistory $history): array
    {
        return [
            'lineId' => $history->lineId,
            'version' => $history->version,
            'frozen' => $history->frozen,
            'systemValue' => self::money($history->systemValue),
            'currentValue' => self::money($history->currentValue),
            'lastCalculatedValue' => self::money($history->lastCalculatedValue),
            'drift' => self::money($history->drift()),
            'entries' => array_map(static fn(HistoryEntry $entry): array => array_filter([
                'kind' => $entry->kind->value,
                'at' => $entry->at->format('Y-m-d\TH:i:s.uP'),
                'amount' => self::money($entry->amount),
                'currentValueAfter' => self::money($entry->currentValueAfter),
                'adjustmentNumber' => $entry->adjustmentNumber,
                'comment' => $entry->comment,
                'specialistId' => $entry->specialistId,
                'drift' => $entry->drift === null ? null : self::money($entry->drift),
            ], static fn(mixed $value): bool => $value !== null), $history->entries),
        ];
    }

    /** @return array{amount: string, currency: string} */
    private static function money(Money $money): array
    {
        return ['amount' => $money->toDecimalString(), 'currency' => $money->currency->value];
    }
}
