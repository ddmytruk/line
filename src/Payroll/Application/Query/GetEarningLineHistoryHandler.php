<?php

declare(strict_types=1);

namespace Payroll\Application\Query;

use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Domain\EarningLineId;

/**
 * Reads the history of a line from the model for reading (ADR 0010). It goes through no bus: reading needs no
 * transaction, no protection against repeats and no retry.
 *
 * A line that has events but no history means the model is out of step; it is reported as not found too, and the cure is a rebuild.
 */
final readonly class GetEarningLineHistoryHandler
{
    public function __construct(private EarningLineHistories $histories) {}

    /** @throws EarningLineNotFound */
    public function __invoke(GetEarningLineHistory $query): EarningLineHistory
    {
        $id = new EarningLineId($query->lineId);

        return $this->histories->find($id->value) ?? throw EarningLineNotFound::withId($id);
    }
}
