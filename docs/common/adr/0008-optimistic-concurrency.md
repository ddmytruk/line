# 0008. Optimistic concurrency on streams

- Status: accepted

## Context
Two writers can load the same line, decide on what they saw and save. If the second save simply appended, the line would get a change that was decided on an outdated state: for example, two specialists who both reverse the same deduction would reverse it twice.

## Decision
- The version of a stream is the number of events in it, the same number as `AggregateRoot::version()` of the aggregate rebuilt from it.
- Every append states the version the decision was based on. A store appends only if the stream is exactly at that version; otherwise it throws `ConcurrencyConflict` and writes nothing.
- Creating an aggregate is an append at version 0, so creating the same aggregate twice is a conflict too.
- The repository takes the expected version from the aggregate: its version minus the events it recorded since it was loaded.
- After a failed save the aggregate object has given its new events away. The repository refuses to save it again; the caller loads the line again and decides on the new state.

## Rejected alternatives
- **Locking a line for as long as a person works on it:** nobody else may change the line until the lock is given back. A specialist may keep a line open for minutes, or leave it open and go away; a forgotten lock blocks everybody else until it expires or an administrator breaks it, and a recalculation needs its own rule for a locked line.
- **Locking the stream in the store for the time of one save:** it puts two saves one after the other, but it does not protect what the specialist saw. The second save waits, then applies a decision made on the state before the first one. A version is needed anyway.
- **Last write wins:** silently loses the decision of one writer or applies a decision made on stale data.

## Consequences
Nothing is ever written over a newer state. The caller has to handle a conflict: read the new state and decide again. Who retries and who asks a human is decided by the caller, not by the store. If the business wants one owner per line, that is a rule about who may adjust a line, not a lock in the store.
