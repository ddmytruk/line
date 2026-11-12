# AI workflow

AI agents write most of the code in this repository. A human makes the decisions and owns the result. This document is the contract between them.

## Roles

1. **Human** decides the scope, accepts the plan and every ADR, and runs the checkpoints of the plan.
2. **Implementing agent** delivers one step of the [plan](plan.md) at a time.
3. **Reviewing agent** is a separate agent without the implementer's context. It reviews the step's diff and its impact zone (see the [git workflow](git-workflow.md)).
4. **Human** decides which findings to accept. Accepted findings are folded into the step; the report goes to `docs/<module>/reviews/` and gets its row in the [review index](../reviews.md), which also logs what the AI got wrong.

## Rules for the code

- **Minimum without losing quality.** Every class, check or abstraction closes a business rule or a real risk. Otherwise it is not added.
- **Comments describe their own layer.** A comment in the domain does not mention handlers, databases or frameworks.
- **Immutable by default.** A property that never changes after construction is `readonly`.

## Definition of Done of a step

- [ ] Code and tests of the step; `make qa` is green (from the skeleton step on).
- [ ] The ADR of every decision taken in the step, with its row in the [decision index](../decisions.md).
- [ ] A `CHANGELOG.md` entry under `[Unreleased]`.
- [ ] The [architecture overview](../architecture.md), the module's `architecture.md` and its other documents match the code.
- [ ] The review is done and its accepted findings are folded into the step.
