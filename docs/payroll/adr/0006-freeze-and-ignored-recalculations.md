# 0006. Freezing a line and recording ignored recalculations

- Status: accepted

## Context
Once a specialist adjusts a line, recalculations must no longer change it. Two details are open in that rule: which system value is frozen, and what happens to a recalculation that is not applied.

## Decision
- **The latest system value is frozen at the first adjustment.** Recalculations before it replace the value, so the adjustments are always relative to the value the specialist saw.
- **Freezing is permanent.** It depends on the existence of an adjustment, not on their sum: adjustments that add up to zero are still a decision of the specialist.
- **A recalculation of a frozen line is recorded** as `SystemRecalculationIgnored` with the value the calculation sent. The line does not change. The history keeps everything the calculation sent, and the difference to the frozen value (drift) can always be derived from it.
- **A value equal to the last value sent is not recorded**, for open and frozen lines alike. A nightly recalculation that changes nothing does not grow the history (open question Q12).
- **Values from the calculation must be in the line's currency**, and the drift of a frozen line must be representable as `Money`; otherwise the value is rejected before it is recorded.

## Rejected alternatives
- **Freezing the first calculated value:** adjustments would be relative to a value the line no longer had when the specialist adjusted it.
- **Dropping recalculations of frozen lines without a trace:** the history would lose what the calculation sent, and a missed payment would be invisible.
- **Unfreezing when the adjustments add up to zero:** arithmetic would undo a decision of a person.

## Consequences
The rule is kept literally, and nothing the calculation sends is lost. How a specialist reacts to drift is a business question (Q1); answering it adds a command and an event without changing these rules.
