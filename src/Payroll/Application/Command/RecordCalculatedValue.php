<?php

declare(strict_types=1);

namespace Payroll\Application\Command;

use Common\Application\Command\SafeToRetryOnConflict;
use Payroll\Application\Exception\UnknownCurrency;
use Payroll\Domain\Currency;
use Payroll\Domain\Money;

/**
 * The payroll calculation sends the whole value of a line. The first value creates the line; later ones are recalculations.
 * The calculation does not decide on what it saw of the line, so the command is safe to run again after a conflict.
 */
final readonly class RecordCalculatedValue implements SafeToRetryOnConflict
{
    public function __construct(
        public string $commandId,
        public string $lineId,
        public string $amount,
        public string $currency,
    ) {}

    /** @throws UnknownCurrency for a currency code that is not supported */
    public function money(): Money
    {
        return Money::fromDecimal($this->amount, Currency::tryFrom($this->currency) ?? throw UnknownCurrency::create());
    }

    public function commandId(): string
    {
        return $this->commandId;
    }
}
