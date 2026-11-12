<?php

declare(strict_types=1);

namespace Common\Application\Command;

/**
 * A request to change the system, sent by a user or another system. Holds only scalar values as they were sent.
 *
 * The id is chosen by the sender and stays the same when the sender repeats the request, so a repeat is recognised.
 */
interface Command
{
    public function commandId(): string;
}
