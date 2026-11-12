<?php

declare(strict_types=1);

namespace Tests\Support\Payroll;

use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\Currency;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Money;
use Payroll\Domain\SpecialistId;

/** Short helpers for building lines in tests. */
final class Lines
{
    public const string LINE_ID = '019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b';

    public static function id(): EarningLineId
    {
        return new EarningLineId(self::LINE_ID);
    }

    public static function usd(string $amount): Money
    {
        return Money::fromDecimal($amount, Currency::USD);
    }

    public static function at(string $time = '2026-01-15 09:00:00'): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time, new \DateTimeZone('UTC'));
    }

    public static function calculated(string $systemValue): EarningLine
    {
        return EarningLine::calculate(new EarningLineId(self::LINE_ID), self::usd($systemValue), self::at());
    }

    public static function adjust(EarningLine $line, string $amount, string $comment = 'Correction'): void
    {
        $line->addManualAdjustment(self::usd($amount), new AdjustmentComment($comment), new SpecialistId('specialist-1'), self::at());
    }
}
