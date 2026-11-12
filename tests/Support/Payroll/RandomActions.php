<?php

declare(strict_types=1);

namespace Tests\Support\Payroll;

use Payroll\Domain\Currency;
use Payroll\Domain\Money;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generates random but reproducible sequences of actions on a line.
 *
 * The seed comes from PROPERTY_SEED (default: fixed), the number of runs from PROPERTY_RUNS (default 200).
 * A failing run prints its seed, so it can be replayed exactly.
 */
final readonly class RandomActions
{
    /** Amounts in cents that floats cannot represent exactly, plus the smallest one. */
    private const array TRICKY_CENTS = [1, 10, 20, 30, 45_55, 100_10];

    private Randomizer $random;

    public function __construct(public int $seed)
    {
        $this->random = new Randomizer(new Mt19937($seed));
    }

    public static function runs(): int
    {
        return max(1, self::env('PROPERTY_RUNS', 200));
    }

    public static function firstSeed(): int
    {
        return self::env('PROPERTY_SEED', 20_260_101);
    }

    private static function env(string $name, int $default): int
    {
        $value = getenv($name);

        if (!\is_string($value) || $value === '') {
            return $default;
        }

        return filter_var($value, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE) ?? throw new \InvalidArgumentException(\sprintf('%s must be an integer, got "%s".', $name, $value));
    }

    /**
     * @return list<array{'recalculate', Money}|array{'adjust', Money}>
     */
    public function actions(Money $firstValue): array
    {
        $actions = [];
        $count = $this->random->getInt(0, 30);
        $sent = [$firstValue];

        for ($i = 0; $i < $count; ++$i) {
            if ($this->random->getInt(0, 2) === 0) {
                // Often the calculation repeats its last value or returns to an earlier one, such as the frozen value.
                $value = match ($this->random->getInt(0, 3)) {
                    0 => $sent[\count($sent) - 1],
                    1 => $sent[$this->random->getInt(0, \count($sent) - 1)],
                    default => $this->systemValue(),
                };
                $sent[] = $value;
                $actions[] = ['recalculate', $value];
            } else {
                $actions[] = ['adjust', $this->adjustment()];
            }
        }

        return $actions;
    }

    public function systemValue(): Money
    {
        return Money::ofMinor($this->random->getInt(0, 1_000_000_00), Currency::USD);
    }

    private function adjustment(): Money
    {
        $cents = $this->random->getInt(0, 1) === 0
            ? self::TRICKY_CENTS[$this->random->getInt(0, \count(self::TRICKY_CENTS) - 1)]
            : $this->random->getInt(1, 100_000_00);

        return Money::ofMinor($this->random->getInt(0, 1) === 0 ? $cents : -$cents, Currency::USD);
    }
}
