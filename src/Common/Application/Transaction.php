<?php

declare(strict_types=1);

namespace Common\Application;

/** Runs work so that everything it writes is kept together, or nothing is kept if it fails. */
interface Transaction
{
    /**
     * @template T
     *
     * @param callable(): T $work
     *
     * @return T
     */
    public function run(callable $work): mixed;
}
