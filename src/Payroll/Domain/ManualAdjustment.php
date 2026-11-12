<?php

declare(strict_types=1);

namespace Payroll\Domain;

/** One saved adjustment of a line. Immutable: there is nothing to edit or delete. */
final readonly class ManualAdjustment
{
    public function __construct(
        public int $number,
        public Money $amount,
        public AdjustmentComment $comment,
        public SpecialistId $specialistId,
        public \DateTimeImmutable $addedAt,
    ) {}
}
