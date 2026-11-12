<?php

declare(strict_types=1);

namespace App\Modules\Common\Database;

use Common\Application\EventStore;
use Common\Application\Exception\ConcurrencyConflict;
use Common\Domain\DomainEvent;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Event store in the `events` table: one row per event, numbered within its stream and across all streams (ADR 0013).
 *
 * A stream's version is checked before writing; a writer that passed the check together with another one is stopped by
 * the unique key of stream and number, so a race ends in the same ConcurrencyConflict as an outdated version.
 */
final readonly class DatabaseEventStore implements EventStore
{
    private const string TABLE = 'events';

    public function __construct(
        private ConnectionInterface $connection,
        private EventSerializer $serializer,
        private int $pageSize = 500,
    ) {}

    public function load(string $streamId): array
    {
        $rows = $this->connection->table(self::TABLE)
            ->where('stream_id', $streamId)
            ->orderBy('stream_version')
            ->get(['event_type', 'payload']);

        return array_values(array_map($this->toEvent(...), $rows->all()));
    }

    public function all(): iterable
    {
        foreach ($this->connection->table(self::TABLE)->select(['position', 'event_type', 'payload'])->lazyById($this->pageSize, 'position') as $row) {
            yield $this->toEvent($row);
        }
    }

    public function append(string $streamId, int $expectedVersion, array $events): void
    {
        if ($events === []) {
            return;
        }

        $rows = [];

        foreach ($events as $i => $event) {
            if ($event->aggregateId() !== $streamId) {
                throw new \InvalidArgumentException(\sprintf('An event of "%s" cannot be appended to stream "%s".', $event->aggregateId(), $streamId));
            }

            $serialized = $this->serializer->serialize($event);
            $rows[] = [
                'stream_id' => $streamId,
                'stream_version' => $expectedVersion + $i + 1,
                'event_type' => $serialized->type,
                'payload' => json_encode($serialized->payload, \JSON_THROW_ON_ERROR),
            ];
        }

        $actualVersion = $this->versionOf($streamId);

        if ($actualVersion !== $expectedVersion) {
            throw ConcurrencyConflict::in($streamId, $expectedVersion, $actualVersion);
        }

        try {
            // One statement: all rows are written or none.
            $this->connection->table(self::TABLE)->insert($rows);
        } catch (UniqueConstraintViolationException) {
            throw ConcurrencyConflict::in($streamId, $expectedVersion, $expectedVersion + 1);
        }
    }

    private function versionOf(string $streamId): int
    {
        $version = $this->connection->table(self::TABLE)->where('stream_id', $streamId)->max('stream_version');

        return is_numeric($version) ? (int) $version : 0;
    }

    private function toEvent(mixed $row): DomainEvent
    {
        \assert(\is_object($row) && isset($row->event_type, $row->payload) && \is_string($row->event_type) && \is_string($row->payload));
        $payload = json_decode($row->payload, true, flags: \JSON_THROW_ON_ERROR);

        if (!\is_array($payload)) {
            throw new \UnexpectedValueException(\sprintf('The payload of a "%s" event is not an object.', $row->event_type));
        }

        /** @var array<string, mixed> $payload */
        return $this->serializer->deserialize(new SerializedEvent($row->event_type, $payload));
    }
}
