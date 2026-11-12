# 0007. A small event-sourcing core of our own

- Status: accepted

## Context
Lines are now stored as their events. That needs a place to keep streams, a way to load an aggregate from its stream and to save what it recorded. Libraries for this exist, but each brings its own aggregate base class, event envelope and serialization, and the domain already has an aggregate root of its own (ADR 0005).

## Decision
- `Common\Application\EventStore` is a port: it loads a stream and appends events to it at an expected version (ADR 0008). Streams are only appended to; there is no operation to change or remove an event.
- A store keeps the events of one aggregate per stream, and the stream id is the aggregate id. A batch that holds an event of another aggregate is refused as a whole.
- Every implementation of the port runs the same contract tests (`tests/Common/Contract/EventStoreContract`). The in-memory store in `app/Modules/Common/InMemory` is a real adapter of the port, not a test double: it passes the same contract tests.
- A module loads and saves its aggregate through its own repository (`Payroll\Application\Command\EarningLines`), which replays the stream and appends what the aggregate recorded. A stored history that breaks the domain's rules is reported as corrupted, never as invalid input.

## Rejected alternatives
- **An event-sourcing library:** its aggregate base class and event envelope would replace or wrap the domain's own, and its serialization would reach into domain events. The core needed here is one port, one exception and a repository of a few dozen lines.
- **A generic repository for any aggregate:** one module has one aggregate; a generic one can be extracted when a second module needs it.

## Consequences
Storage of any kind is added as one more adapter that passes the contract tests. What a library would give for free (serialization, snapshots, upcasting of old events) is written only when a real need appears.
