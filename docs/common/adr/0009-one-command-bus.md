# 0009. One command bus with a transaction and idempotency

- Status: accepted

## Context
Lines are now changed by commands from two senders: the payroll calculation and specialists. A sender may repeat a command when it does not know whether the first attempt went through, for example after a timeout. Two copies of an adjustment would change the pay. A command can also fail halfway, after some of its writes.

## Decision
- Commands from senders are carried out by `Common\Application\Command\CommandBus`. A command carries an id chosen by its sender; the id stays the same when the sender repeats the command.
- The bus runs the handler in a `Transaction`: the events, and the record that the command was carried out, are kept together or not at all.
- Before running a handler, the bus looks the id up in `ProcessedCommands`. A known id with the same content does nothing; a known id with other content is refused with `CommandIdReused`. The content is compared by a fingerprint of the whole command, including its class; a store that keeps ids across releases must keep the fingerprint stable when a command class is renamed or reshaped, or a legitimate repeat would be refused.
- Two runs of the same command can both see the id as new. The store of processed commands accepts an id only once, so the second run fails, its writes are dropped, and the bus runs it again, which now finds the id and does nothing.
- On a `ConcurrencyConflict` (ADR 0008) the bus runs a command again once only if the command is marked `SafeToRetryOnConflict`: its decision does not depend on what its sender saw. A command decided by a person is returned to the sender, who has to see the new state first.
- Handlers take the time from a `Clock` port, so the time of a fact is the time it was recorded.

## Rejected alternatives
- **A bus library with middleware:** a configurable pipeline for three fixed steps (transaction, idempotency, handler).
- **Idempotency by checking the line's state** (for example, "this adjustment already exists"): two equal adjustments can be two real, separate corrections; only the sender knows whether it repeated itself.
- **Retrying every conflict:** an adjustment decided on a value the specialist did not see would be applied silently.

## Consequences
Repeating a command is always safe for the sender. In the rare case that a copy of a safe command is carried out during its retry, the sender gets `CommandAlreadyProcessed`, although the command was carried out once. Processed ids are kept for good, which is an open question (Q14). A handler must not have effects outside the transaction, such as sending messages, or they would not be undone.
