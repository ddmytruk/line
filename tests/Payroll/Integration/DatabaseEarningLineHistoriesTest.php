<?php

declare(strict_types=1);

namespace Tests\Payroll\Integration;

use App\Modules\Payroll\Database\DatabaseEarningLineHistories;
use Payroll\Application\History\EarningLineHistories;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Common\Integration\TestApplication;
use Tests\Payroll\Contract\EarningLineHistoriesContract;

#[CoversClass(DatabaseEarningLineHistories::class)]
final class DatabaseEarningLineHistoriesTest extends EarningLineHistoriesContract
{
    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
    }

    protected function createStore(): EarningLineHistories
    {
        return new DatabaseEarningLineHistories(TestApplication::connection());
    }
}
