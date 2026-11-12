<?php

declare(strict_types=1);

namespace Common\Application\Command;

/**
 * A command whose decision does not depend on what its sender saw, so the bus may run it again on the new state
 * after a concurrency conflict. A command decided by a person on what they saw must not be retried: they have to see the change first.
 */
interface SafeToRetryOnConflict extends Command {}
