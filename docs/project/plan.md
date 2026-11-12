# Plan

The project is delivered in releases. Each release is a tag, and each step of a release is one commit.

- **1.0.0 Domain model:** the business rules as an event-recording aggregate, their tests and a command-line demo, in plain PHP without a framework or storage.
- **1.1.0 Event sourcing and CQRS:** lines are stored as their events, changed through commands and read through a separate history model. Still plain PHP: storage sits behind ports with in-memory adapters, and the domain does not change.
- **1.2.0 Laravel and a database:** the same core runs as a Laravel application with MySQL; Laravel and the database are adapters of the existing ports, and `src/` does not change.

Each step below is one commit: code, tests, the ADR of a decision taken in that step, a `CHANGELOG.md` entry and the matching update of the architecture documents (the [overview](../architecture.md) and the module's `architecture.md`). How commits and reviews work is described in the [git workflow](git-workflow.md).

## 1.0.0 Domain model

- [x] **0. Plan and rules.** This plan, git and AI workflows, ADR template, domain rules and open questions, architecture overview. ADR 0001.
- [x] **1. Skeleton.** Composer, PHPUnit, PHPStan at max level with architecture rules, coding style, Docker, Makefile, CI. ADR 0002.
- [x] **2. Value objects.** `Money` in integer minor units with `Currency`, `AdjustmentComment`, identifiers. ADR 0003, ADR 0004.
- [x] **3. Aggregate.** `EarningLine` that changes state only through events; freeze and ignored recalculations. ADR 0005, ADR 0006.
- [x] **4. Executable specification.** The reference scenario step by step, and property-based tests of the invariants.
- [x] **5. Command-line demo.** `bin/earning-line demo`, README for the release. Tag `v1.0.0`.

## Out of scope of 1.0.0

- **Business decisions** that are not defined yet: period closing, reviewing drift, roles and approvals. Each is listed with its assumption and extension point in the [open questions](../payroll/domain.md#open-questions).
- **Technical topics:** storing events, a command and query side, a framework, a database and a user interface. The command line is the only interface, and the line lives in memory for the time of a command.

## 1.1.0 Event sourcing and CQRS

- [x] **6. Event store and repository.** `EventStore` port with optimistic concurrency, in-memory adapter with contract tests, `EarningLines` repository; one folder layout for every layer. ADR 0007, ADR 0008.
- [x] **7. Commands and the command bus.** Commands with ids, handlers, one bus with a transaction and idempotency; the reference scenario through the bus. ADR 0009.
- [x] **8. History read model.** The line history with drift, built by a synchronous projector, rebuilt from the events and read through a query. ADR 0010, ADR 0011.
- [x] **9. Command-line demo through the bus.** The demo writes through the bus and reads the history model; README for the release. Tag `v1.1.0`.

## Out of scope of 1.1.0

- **Business decisions:** the same open questions as in 1.0.0, plus those that commands add, are listed in the [open questions](../payroll/domain.md#open-questions).
- **Technical topics:** a storage format for events, a database, a framework, asynchronous projectors, snapshots and event versioning. Stores of this release keep their data in memory only.

## 1.2.0 Laravel and a database

- [x] **10. Laravel application and MySQL.** Laravel inside `app/`, MySQL in Docker Compose and CI, a test suite against the database. ADR 0012.
- [x] **11. Stores in the database.** Event store, processed commands and transaction in MySQL; stored events cannot be changed or deleted; the same contract tests as the in-memory stores. ADR 0013.
- [x] **12. Payroll on Laravel.** Payroll events stored under stable names, the line history in a table, artisan commands and an HTTP API. ADR 0014.
- [ ] **13. Mutation testing and release.** Mutation testing of the core in CI, README for the release. Tag `v1.2.0`.
