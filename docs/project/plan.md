# Plan

Release 1.0.0 is the domain model: the business rules as an event-recording aggregate, their tests and a command-line demo, in plain PHP without a framework or storage.

Each step below is one commit: code, tests, the ADR of a decision taken in that step, a `CHANGELOG.md` entry and the matching update of the architecture documents (the [overview](../architecture.md) and the module's `architecture.md`). How commits and reviews work is described in the [git workflow](git-workflow.md).

## 1.0.0 Domain model

- [x] **0. Plan and rules.** This plan, git and AI workflows, ADR template, domain rules and open questions, architecture overview. ADR 0001.
- [x] **1. Skeleton.** Composer, PHPUnit, PHPStan at max level with architecture rules, coding style, Docker, Makefile, CI. ADR 0002.
- [ ] **2. Value objects.** `Money` in integer minor units with `Currency`, `AdjustmentComment`, identifiers. ADR 0003, ADR 0004.
- [ ] **3. Aggregate.** `EarningLine` that changes state only through events; freeze and ignored recalculations. ADR 0005, ADR 0006.
- [ ] **4. Executable specification.** The reference scenario step by step, and property-based tests of the invariants.
- [ ] **5. Command-line demo.** `bin/earning-line demo`, README for the release. Tag `v1.0.0`.

## Out of scope of 1.0.0

- **Business decisions** that are not defined yet: period closing, reviewing drift, roles and approvals. Each is listed with its assumption and extension point in the [open questions](../payroll/domain.md#open-questions).
- **Technical topics:** storing events, a command and query side, a framework, a database and a user interface. The command line is the only interface, and the line lives in memory for the time of a command.
