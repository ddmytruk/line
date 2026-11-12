# 0011. Drift is shown in the history, not decided in the line

- Status: accepted

## Context
After a line is frozen, the calculation keeps sending values, and the line records them as ignored (ADR 0006). Without a signal, a calculation that now disagrees with the frozen value would go unnoticed: the employee could be paid on an outdated base while the source data says otherwise.

## Decision
- The history of a line shows drift: how far the latest calculated value is from the frozen system value. Each ignored recalculation shows its own drift, and the history shows the current one.
- Drift is computed by the history projector from the events the line already records. The line does not store it and no rule depends on it.
- An open line has no drift: its system value is the latest calculated value.

## Rejected alternatives
- **Drift as state of the aggregate:** nothing in the rules uses it, so it would be state without a reason in the domain.
- **Applying the drift as an automatic adjustment:** breaks the rule that the specialist's corrections take precedence over the calculation.

## Consequences
Drift is visible without changing the domain. What a specialist does about drift is open question Q1; an answer such as "drift acknowledged" adds a command and an event, and the history shows it.
