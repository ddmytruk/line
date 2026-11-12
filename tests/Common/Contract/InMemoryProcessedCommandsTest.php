<?php

declare(strict_types=1);

namespace Tests\Common\Contract;

use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use Common\Application\Command\ProcessedCommands;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryProcessedCommands::class)]
final class InMemoryProcessedCommandsTest extends ProcessedCommandsContract
{
    protected function createStore(): ProcessedCommands
    {
        return new InMemoryProcessedCommands();
    }
}
