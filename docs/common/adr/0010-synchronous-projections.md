# 0010. Models for reading, built synchronously from events

- Status: accepted

## Context
A specialist reads a line as a history: the current value and every fact that produced it. Rebuilding the line from its events and turning the aggregate into a view would do for one line, but it ties every screen to the aggregate and repeats the work on every read. A model built for reading has its own risk: it can fall behind the events or disagree with them.

## Decision
- A `Projector` is the code that turns events into a model for reading; the model is the data it builds (a read model). `ProjectingEventStore` hands every appended event to the projectors right after the append, inside the same transaction as the events and the processed command. A model for reading is therefore never behind the events: either both are written or neither is.
- A projector only reads what events say. It applies no rules; the rules were applied when the events were recorded.
- An event of an entity the model does not know, or a second first event of one it knows, means the model is out of step with the events. The projector throws instead of guessing; the cure is a rebuild.
- `ReadModelRebuild` forgets the models and projects every stored event again, in the order of appending, in one transaction, so a failed rebuild leaves the models as they were. It is how a lost or changed model is brought back, and it must give the same model as building it along the way.
- Every store of a model for reading runs the same contract tests, like every event store.
- A module reads its models through queries: `Command/` of a module holds the requests that change it and `Query/` the requests that read it. A query is a small object, and its handler returns the model or reports that it does not exist. Queries go through no bus: the command bus gives a transaction, protection against repeats and a retry, and reading needs none of them.

## Rejected alternatives
- **Asynchronous projectors with checkpoints:** they scale reads and writes apart, but a specialist would sometimes read a history without their own adjustment, and the version they send back would be outdated. Nothing here needs that scale.
- **Reading by rebuilding the aggregate:** no second model to keep in step, but the view is tied to the aggregate's internals and the work is repeated on every read.
- **A query bus like the command bus:** the same shape on both sides, but a class that only passes a query to its handler and adds nothing that reading needs.
- **Reading the store of a model directly:** one class less, but there would be no single place for a line that does not exist, and no obvious place for the reading side next to `Command/`.

## Consequences
Reads are simple and always current. A write takes as long as its projectors. A projector must be fast and must not call anything outside the transaction. A rebuild reads every event, which is fine while the number of events is small; snapshots or checkpoints come with a real need.

A projector that fails fails the command, whether because of a bug or because the store of the model refuses a write. The events are not written either, so nothing is lost and nothing disagrees, but every write that the projector fails on is refused until the projector is fixed; for a bug, that can be every write. A refused save is preferred to a history that disagrees with the events, so projectors are kept simple and only copy what events say. Revisit this decision if a projector becomes slow or has to reach outside the transaction: such a projector would run asynchronously, and its model could be behind the events for a moment.
