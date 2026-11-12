<?php

declare(strict_types=1);

namespace Payroll\Application\Command;

use Common\Application\Clock;
use Common\Application\Exception\ConcurrencyConflict;
use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\SpecialistId;

final readonly class AddManualAdjustmentHandler
{
    public function __construct(
        private EarningLines $lines,
        private Clock $clock,
    ) {}

    public function __invoke(AddManualAdjustment $command): void
    {
        $line = $this->lines->get(new EarningLineId($command->lineId));

        if ($line->version() !== $command->expectedVersion) {
            throw ConcurrencyConflict::in($line->aggregateId(), $command->expectedVersion, $line->version());
        }

        $line->addManualAdjustment(
            $command->money(),
            new AdjustmentComment($command->comment),
            new SpecialistId($command->specialistId),
            $this->clock->now(),
        );

        $this->lines->save($line);
    }
}
