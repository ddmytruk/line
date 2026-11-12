<?php

declare(strict_types=1);

namespace Payroll\Application\Command;

use Common\Application\Clock;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;

final readonly class RecordCalculatedValueHandler
{
    public function __construct(
        private EarningLines $lines,
        private Clock $clock,
    ) {}

    public function __invoke(RecordCalculatedValue $command): void
    {
        $id = new EarningLineId($command->lineId);
        $value = $command->money();
        $line = $this->lines->find($id);

        if ($line === null) {
            $line = EarningLine::calculate($id, $value, $this->clock->now());
        } else {
            $line->recordSystemValue($value, $this->clock->now());
        }

        $this->lines->save($line);
    }
}
