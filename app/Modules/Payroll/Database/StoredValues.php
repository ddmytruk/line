<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Database;

use Payroll\Domain\Currency;
use Payroll\Domain\Money;

/**
 * How amounts and times of Payroll are written into stored JSON, and how they are read back.
 * Amounts are exact decimal strings with their currency; times are UTC with microseconds.
 */
final class StoredValues
{
    private const string TIME_FORMAT = 'Y-m-d\TH:i:s.u\Z';

    /** @return array{amount: string, currency: string} */
    public static function money(Money $money): array
    {
        return ['amount' => $money->toDecimalString(), 'currency' => $money->currency->value];
    }

    /** @throws \UnexpectedValueException if the data is not a stored amount */
    public static function toMoney(mixed $data): Money
    {
        if (!\is_array($data) || !\is_string($data['amount'] ?? null) || !\is_string($data['currency'] ?? null)) {
            throw new \UnexpectedValueException('A stored amount needs an amount and a currency.');
        }

        $currency = Currency::tryFrom($data['currency']) ?? throw new \UnexpectedValueException(\sprintf('Unknown stored currency "%s".', $data['currency']));

        return Money::fromDecimal($data['amount'], $currency);
    }

    public static function time(\DateTimeImmutable $time): string
    {
        return $time->setTimezone(new \DateTimeZone('UTC'))->format(self::TIME_FORMAT);
    }

    /** @throws \UnexpectedValueException if the data is not a stored time */
    public static function toTime(mixed $data): \DateTimeImmutable
    {
        $time = \is_string($data) ? \DateTimeImmutable::createFromFormat('!' . self::TIME_FORMAT, $data, new \DateTimeZone('UTC')) : false;

        return $time !== false ? $time : throw new \UnexpectedValueException('A stored time must be UTC with microseconds.');
    }

    /**
     * @param array<mixed> $data
     *
     * @throws \UnexpectedValueException if the field is missing or not a string
     */
    public static function string(array $data, string $field): string
    {
        $value = $data[$field] ?? null;

        return \is_string($value) ? $value : throw new \UnexpectedValueException(\sprintf('The stored field "%s" must be a string.', $field));
    }

    /**
     * @param array<mixed> $data
     *
     * @throws \UnexpectedValueException if the field is missing or not an integer
     */
    public static function int(array $data, string $field): int
    {
        $value = $data[$field] ?? null;

        return \is_int($value) ? $value : throw new \UnexpectedValueException(\sprintf('The stored field "%s" must be an integer.', $field));
    }
}
