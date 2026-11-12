# Changelog

All notable changes to this project are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Project plan, git workflow, AI workflow and the ADR template.
- Business rules, line lifecycle and open questions of the Payroll module.
- Architecture overview: context, layers and modules (ADR 0001).
- Project skeleton: Composer, PHPUnit, PHPStan at max level with architecture rules, PHP CS Fixer, Docker, Makefile and CI (ADR 0002).
- `Money` in integer minor units with `Currency` (ADR 0003), `AdjustmentComment`, `EarningLineId` and `SpecialistId` (ADR 0004).
- `AggregateRoot` whose state changes only through events (ADR 0005).
- `EarningLine` aggregate: calculation, recalculation, manual adjustments, freezing and recorded ignored recalculations (ADR 0006).
