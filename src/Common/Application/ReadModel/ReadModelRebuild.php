<?php

declare(strict_types=1);

namespace Common\Application\ReadModel;

use Common\Application\EventStore;
use Common\Application\Transaction;

/** Builds the models for reading again from every stored event, for example after a model was lost or its code changed. */
final readonly class ReadModelRebuild
{
    /** @param list<Projector> $projectors */
    public function __construct(
        private Transaction $transaction,
        private EventStore $store,
        private array $projectors,
    ) {}

    /** Runs in a transaction: if a projector fails, the models stay as they were, never half built. */
    public function run(): void
    {
        $this->transaction->run(function (): void {
            foreach ($this->projectors as $projector) {
                $projector->reset();
            }

            foreach ($this->store->all() as $event) {
                foreach ($this->projectors as $projector) {
                    $projector->project($event);
                }
            }
        });
    }
}
