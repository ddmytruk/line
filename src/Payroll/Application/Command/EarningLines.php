<?php

declare(strict_types=1);

namespace Payroll\Application\Command;

use Common\Application\EventStore;
use Common\Application\Exception\ConcurrencyConflict;
use Payroll\Application\Exception\CorruptedEarningLineEvents;
use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;

/** Earning lines stored as their events: a line is loaded by replaying its stream and saved by appending what it recorded. */
final class EarningLines
{
    /** @var \WeakMap<EarningLine, true> lines whose save failed: they gave their new events away, so they cannot be saved again */
    private \WeakMap $failed;

    public function __construct(private readonly EventStore $events)
    {
        $this->failed = new \WeakMap();
    }

    /** @throws EarningLineNotFound|CorruptedEarningLineEvents */
    public function get(EarningLineId $id): EarningLine
    {
        return $this->find($id) ?? throw EarningLineNotFound::withId($id);
    }

    /** @throws CorruptedEarningLineEvents */
    public function find(EarningLineId $id): ?EarningLine
    {
        $history = $this->events->load($id->value);

        if ($history === []) {
            return null;
        }

        try {
            return EarningLine::reconstitute($history);
        } catch (\UnexpectedValueException|\DomainException $exception) { // @phpstan-ignore catch.neverThrown (replay also throws the domain's own exceptions from apply(), which are not declared)
            // A stored history that breaks the rules is never user input: it is reported as corrupted (ADR 0005).
            throw CorruptedEarningLineEvents::of($id, $exception);
        }
    }

    /**
     * Appends the events the line recorded since it was loaded or created.
     *
     * @throws ConcurrencyConflict if the line changed in the store after it was loaded; nothing is saved,
     *                             and the line has to be loaded again before it can be changed and saved
     * @throws \LogicException     if an earlier save of this line object failed
     */
    public function save(EarningLine $line): void
    {
        if (isset($this->failed[$line])) {
            throw new \LogicException('A line whose save failed must be loaded again before it is saved.');
        }

        $events = $line->releaseEvents();

        try {
            $this->events->append($line->aggregateId(), $line->version() - \count($events), $events);
        } catch (\Throwable $failure) {
            // The line no longer holds the events it could not save; saving it again would silently drop them.
            $this->failed[$line] = true;

            throw $failure;
        }
    }
}
