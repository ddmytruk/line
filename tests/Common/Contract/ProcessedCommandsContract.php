<?php

declare(strict_types=1);

namespace Tests\Common\Contract;

use Common\Application\Command\ProcessedCommands;
use Common\Application\Exception\CommandAlreadyProcessed;
use PHPUnit\Framework\TestCase;

/** What every store of processed commands must guarantee. Each implementation runs these tests by extending this class. */
abstract class ProcessedCommandsContract extends TestCase
{
    abstract protected function createStore(): ProcessedCommands;

    public function testAnUnknownCommandHasNoFingerprint(): void
    {
        self::assertNull($this->createStore()->fingerprintOf('c-1'));
    }

    public function testRemembersTheFingerprintOfACommand(): void
    {
        $store = $this->createStore();
        $store->add('c-1', 'fingerprint-1');
        $store->add('c-2', 'fingerprint-2');

        self::assertSame('fingerprint-1', $store->fingerprintOf('c-1'));
        self::assertSame('fingerprint-2', $store->fingerprintOf('c-2'));
    }

    public function testIdsDifferingOnlyInLetterCaseOrTrailingSpacesAreDifferentCommands(): void
    {
        $store = $this->createStore();
        $store->add('c-1', 'fingerprint-1');
        $store->add('C-1', 'fingerprint-2');
        $store->add('c-1 ', 'fingerprint-3');

        self::assertSame('fingerprint-1', $store->fingerprintOf('c-1'));
        self::assertSame('fingerprint-2', $store->fingerprintOf('C-1'));
        self::assertSame('fingerprint-3', $store->fingerprintOf('c-1 '));
    }

    public function testRefusesToStoreAnIdTwiceAndKeepsTheFirstFingerprint(): void
    {
        $store = $this->createStore();
        $store->add('c-1', 'fingerprint-1');

        try {
            $store->add('c-1', 'fingerprint-2');
            self::fail('An id must be stored only once.');
        } catch (CommandAlreadyProcessed) {
        }

        self::assertSame('fingerprint-1', $store->fingerprintOf('c-1'));
    }
}
