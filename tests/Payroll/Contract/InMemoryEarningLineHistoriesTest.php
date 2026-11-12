<?php

declare(strict_types=1);

namespace Tests\Payroll\Contract;

use App\Modules\Payroll\InMemory\InMemoryEarningLineHistories;
use Payroll\Application\History\EarningLineHistories;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryEarningLineHistories::class)]
final class InMemoryEarningLineHistoriesTest extends EarningLineHistoriesContract
{
    protected function createStore(): EarningLineHistories
    {
        return new InMemoryEarningLineHistories();
    }
}
