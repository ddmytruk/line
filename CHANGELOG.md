# Changelog

All notable changes to this project are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- `EventStore` port with optimistic concurrency, an in-memory event store and contract tests that every store runs (ADR 0007, ADR 0008).
- `EarningLines` repository: loads a line by replaying its events and saves what it recorded; a stored history that breaks the rules is reported as corrupted.
- One folder layout for every layer, described in the architecture overview; phpat checks that every exception lives in the `Exception/` folder of its layer.
- `CommandBus`: runs each command in a transaction, at most once per command id, and runs a command that is safe to retry again after a concurrency conflict (ADR 0009). In-memory transaction and store of processed commands, with contract tests.
- Payroll commands `RecordCalculatedValue` (creates or recalculates a line) and `AddManualAdjustment` (refused if the line changed since the specialist saw it), with their handlers; the reference scenario runs through the bus.

## [1.0.0] - 2026-11-12

### Added
- Project plan, git workflow, AI workflow and the ADR template.
- Business rules, line lifecycle and open questions of the Payroll module.
- Architecture overview: context, layers and modules (ADR 0001).
- Project skeleton: Composer, PHPUnit, PHPStan at max level with architecture rules, PHP CS Fixer, Docker, Makefile and CI (ADR 0002).
- `Money` in integer minor units with `Currency` (ADR 0003), `AdjustmentComment`, `EarningLineId` and `SpecialistId` (ADR 0004).
- `AggregateRoot` whose state changes only through events (ADR 0005).
- `EarningLine` aggregate: calculation, recalculation, manual adjustments, freezing and recorded ignored recalculations (ADR 0006).
- The reference scenario as an executable specification, and property-based tests of the line's invariants on random, reproducible sequences of actions.
- Command-line demo `bin/earning-line demo` that plays the reference scenario and prints the history and the full log.
