<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/** Runtime assumptions that Composer does not check. */
#[CoversNothing]
final class EnvironmentTest extends TestCase
{
    /** The plan keeps money in integer minor units; a 32-bit integer would overflow at about 21 million. */
    public function testIntegersAre64Bit(): void
    {
        self::assertSame(8, \PHP_INT_SIZE);
    }
}
