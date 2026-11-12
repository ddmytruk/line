<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Domain;

use Payroll\Domain\Currency;
use Payroll\Domain\Exception\CurrencyMismatch;
use Payroll\Domain\Exception\InvalidMoneyAmount;
use Payroll\Domain\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Money::class)]
#[CoversClass(Currency::class)]
#[CoversClass(InvalidMoneyAmount::class)]
#[CoversClass(CurrencyMismatch::class)]
final class MoneyTest extends TestCase
{
    /** @return iterable<string, array{string, int}> */
    public static function exactAmounts(): iterable
    {
        yield 'whole' => ['1050', 105000];
        yield 'one decimal place' => ['1050.5', 105050];
        yield 'negative' => ['-45.55', -4555];
        yield 'explicit plus' => ['+100.10', 10010];
        yield 'below one unit' => ['-0.10', -10];
        yield 'surrounding spaces' => [' 1.00 ', 100];
        yield 'negative zero' => ['-0', 0];
        yield 'largest amount' => ['9999999999999.99', Money::MAX_MINOR_UNITS];
        yield 'smallest amount' => ['-9999999999999.99', -Money::MAX_MINOR_UNITS];
    }

    #[DataProvider('exactAmounts')]
    public function testParsesExactAmountsIntoMinorUnits(string $input, int $expectedMinorUnits): void
    {
        self::assertSame($expectedMinorUnits, Money::fromDecimal($input, Currency::USD)->minorUnits);
    }

    /** @return iterable<string, array{string}> */
    public static function inexactAmounts(): iterable
    {
        yield 'fraction of a cent' => ['1.005'];
        yield 'decimal comma' => ['1,50'];
        yield 'not a number' => ['abc'];
        yield 'empty' => [''];
        yield 'scientific notation' => ['1e3'];
        yield 'too many digits' => ['99999999999999'];
        yield 'too many negative digits' => ['-99999999999999'];
        yield 'no digits before the point' => ['.5'];
        yield 'no digits after the point' => ['1.'];
    }

    #[DataProvider('inexactAmounts')]
    public function testRejectsInexactAmounts(string $input): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::fromDecimal($input, Currency::USD);
    }

    public function testAddsWithoutFloatingPointErrors(): void
    {
        $sum = Money::fromDecimal('-0.10', Currency::USD)->add(Money::fromDecimal('-0.20', Currency::USD));

        self::assertSame(-30, $sum->minorUnits);
        self::assertSame('0.30', Money::fromDecimal('0.10', Currency::USD)->add(Money::fromDecimal('0.20', Currency::USD))->toDecimalString());
    }

    public function testSubtracts(): void
    {
        $difference = Money::fromDecimal('1100', Currency::USD)->subtract(Money::fromDecimal('1050.10', Currency::USD));

        self::assertSame('49.90', $difference->toDecimalString());
        self::assertSame('-49.90', Money::ofMinor(0, Currency::USD)->subtract($difference)->toDecimalString());
    }

    public function testKnowsZero(): void
    {
        self::assertTrue(Money::ofMinor(0, Currency::USD)->isZero());
        self::assertFalse(Money::ofMinor(-1, Currency::USD)->isZero());
    }

    public function testRefusesToAddAnotherCurrency(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Money::fromDecimal('1', Currency::USD)->add(Money::fromDecimal('1', Currency::EUR));
    }

    public function testRefusesToSubtractAnotherCurrency(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Money::fromDecimal('1', Currency::USD)->subtract(Money::fromDecimal('1', Currency::EUR));
    }

    public function testAcceptsTheEdgesOfTheSupportedRange(): void
    {
        self::assertSame(Money::MAX_MINOR_UNITS, Money::ofMinor(Money::MAX_MINOR_UNITS, Currency::USD)->minorUnits);
        self::assertSame(-Money::MAX_MINOR_UNITS, Money::ofMinor(-Money::MAX_MINOR_UNITS, Currency::USD)->minorUnits);
    }

    public function testRejectsAnAmountJustOutsideTheSupportedRange(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::ofMinor(Money::MAX_MINOR_UNITS + 1, Currency::USD);
    }

    public function testRejectsTheSmallestInteger(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::ofMinor(\PHP_INT_MIN, Currency::USD);
    }

    public function testRejectsASumOutsideTheSupportedRange(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::ofMinor(Money::MAX_MINOR_UNITS, Currency::USD)->add(Money::ofMinor(1, Currency::USD));
    }

    public function testRejectsADifferenceOutsideTheSupportedRange(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::ofMinor(-Money::MAX_MINOR_UNITS, Currency::USD)->subtract(Money::ofMinor(1, Currency::USD));
    }

    public function testComparesByAmountAndCurrency(): void
    {
        self::assertTrue(Money::fromDecimal('1', Currency::USD)->equals(Money::ofMinor(100, Currency::USD)));
        self::assertFalse(Money::fromDecimal('1', Currency::USD)->equals(Money::ofMinor(100, Currency::EUR)));
        self::assertFalse(Money::fromDecimal('1', Currency::USD)->equals(Money::ofMinor(101, Currency::USD)));
    }

    /** @return iterable<string, array{int, string}> */
    public static function decimalStrings(): iterable
    {
        yield 'zero' => [0, '0.00'];
        yield 'cents only' => [-5, '-0.05'];
        yield 'whole and cents' => [110445, '1104.45'];
    }

    #[DataProvider('decimalStrings')]
    public function testFormatsAsAPlainDecimalString(int $minorUnits, string $expected): void
    {
        self::assertSame($expected, Money::ofMinor($minorUnits, Currency::USD)->toDecimalString());
    }

}
