# 0005. Aggregate state changes only through events

- Status: accepted

## Context
The history of a line is the thing being audited, and the current value must always be explainable by it. If the state were changed directly and the history written next to it, the two could disagree, and nothing would show which one is right.

## Decision
- An aggregate decides in a public method (check the rules, reject or accept) and then records an event. Only `apply()` changes state, and it decides nothing.
- The same `apply()` runs for a new event and for replaying the history (`AggregateRoot::reconstitute`). An aggregate rebuilt from its events therefore has exactly the state it had when it recorded them.
- New events wait in the aggregate until they are released (`releaseEvents()`); `version()` counts all applied events, so a store can detect concurrent changes.
- Replaying a history that contradicts the rules (wrong first event, a recalculation of a frozen line, a gap in adjustment numbers, an unknown event) throws. A corrupted history is never silently repaired.
- Events carry value objects, not primitives. Turning them into storage formats is the job of the storage adapter.

- Replay fails with `UnexpectedValueException` for a broken sequence, and with the domain's own exception when a value inside an event breaks a rule (another currency, a sum out of range). Code that loads an aggregate treats any failure of `reconstitute` as a corrupted history, never as invalid user input.
- Events are rebuilt through the validating constructors of value objects. Limits on stored values (comment length, id format) may therefore only be relaxed in the domain; a stricter limit for new input belongs to the application layer, or stored history would stop loading.

## Rejected alternatives
- **A state-based entity with a separate audit log:** two sources of truth that can drift apart.
- **Serialization methods on events (`toArray`/`fromArray`):** a storage concern inside the domain; it would change whenever the storage format changes.
- **Tolerant replay that skips unknown events:** a line could come back with different money and no error.

## Consequences
The domain records events even though nothing stores them yet; storing them adds code around the domain without changing it. The cost is one `apply` method per event.
