<?php

declare(strict_types=1);

namespace Payroll\Application\Command;

use Common\Application\Command\Command;
use Payroll\Application\Exception\UnknownCurrency;
use Payroll\Domain\Currency;
use Payroll\Domain\Money;

/**
 * A specialist corrects a line by a signed amount with a comment.
 *
 * The expected version is the version of the line the specialist looked at. If the line changed since then,
 * the adjustment is refused, so nobody corrects a value they have not seen.
 */
final readonly class AddManualAdjustment implements Command
{
    public function __construct(
        public string $commandId,
        public string $lineId,
        public int $expectedVersion,
        public string $amount,
        public string $currency,
        public string $comment,
        public string $specialistId,
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
