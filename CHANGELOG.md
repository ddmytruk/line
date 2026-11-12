# Changelog

All notable changes to this project are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.2.1] - 2026-11-13

### Added
- CI status badge in the README.

### Changed
- CI uses `actions/checkout@v6` and `ramsey/composer-install@v4`, which run on Node.js 24; GitHub no longer supports Node.js 20 for actions.
- CI runs on `ubuntu-24.04` instead of `ubuntu-latest`, so a new runner image does not change the build unannounced.
- Mutation testing does not generate the five changes that change nothing a test could observe, and requires every other change to be caught (100 % instead of 97.8 %); CI shows no annotations for them any more.

## [1.2.0] - 2026-11-12

### Added
- Laravel application inside `app/` with MySQL in Docker Compose and CI; `make artisan` and `make test-integration` for tests against the database (ADR 0012).
- Architecture rule: the in-memory adapters and the command-line demo do not depend on Laravel.
- Event store, processed commands and transaction in MySQL, with the same contract tests as the in-memory ones and tests of two writers at once; triggers refuse to change or delete stored events (ADR 0013).
- Contract tests for transactions; a run inside another run undoes only its own work.
- Payroll on MySQL: events stored under fixed type names, the line history as one JSON document per line, the module wired in `PayrollServiceProvider` (ADR 0014).
- Artisan commands `payroll:record-value`, `payroll:adjust`, `payroll:history` and `payroll:rebuild`.
- HTTP API: `POST /lines/{id}/calculations`, `POST /lines/{id}/adjustments`, `GET /lines/{id}/history`.
- `SystemClock`: the time of the machine in UTC.
- Open question Q17: how the system knows which specialist sends an adjustment.
- Mutation testing of the core with Infection in CI and `make mutation`; the score must stay at 97.8 % or more.
- `make serve` serves the HTTP API on localhost:8000 (`PORT=` for another port); MySQL of Docker Compose is reachable from the host at 127.0.0.1:3307 (`MYSQL_PORT=` for another port); README for the release, with an overview of the releases.

### Changed
- The Docker image has pcov for mutation testing; an existing image needs `make build`.

## [1.1.0] - 2026-11-12

### Added
- `EventStore` port with optimistic concurrency, an in-memory event store and contract tests that every store runs (ADR 0007, ADR 0008).
- `EarningLines` repository: loads a line by replaying its events and saves what it recorded; a stored history that breaks the rules is reported as corrupted.
- One folder layout for every layer, described in the architecture overview; phpat checks that every exception lives in the `Exception/` folder of its layer.
- `CommandBus`: runs each command in a transaction, at most once per command id, and runs a command that is safe to retry again after a concurrency conflict (ADR 0009). In-memory transaction and store of processed commands, with contract tests.
- Payroll commands `RecordCalculatedValue` (creates or recalculates a line) and `AddManualAdjustment` (refused if the line changed since the specialist saw it), with their handlers; the reference scenario runs through the bus.
- Projectors: every appended event is projected in the same transaction, and `ReadModelRebuild` builds the models for reading again from all events (ADR 0010). `EventStore::all()` lists the events of all streams in the order of appending.
- Line history for reading: current value, every fact with the value after it, adjustments with author and comment, and drift of a frozen line (ADR 0011), read through the `GetEarningLineHistory` query. In-memory store of histories with contract tests.

### Changed
- The command-line demo sends the reference scenario as commands through the bus and prints the history from the model for reading, with drift. It also shows a repeated command, an adjustment of an outdated version and a rebuild of the history.

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
