<?php

declare(strict_types=1);

namespace Payroll\Application\Exception;

use Payroll\Domain\EarningLineId;

final class EarningLineNotFound extends \RuntimeException
{
    public static function withId(EarningLineId $id): self
    {
        return new self(\sprintf('Earning line %s does not exist.', $id->value));
    }
}
