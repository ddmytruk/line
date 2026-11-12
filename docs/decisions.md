# Architecture decisions

Every ADR of the repository, in one list. An ADR lives in the folder of the module it is about, but the number is shared by the whole repository, so this list is where the next number comes from.

To add an ADR: take the next number after the last row, copy the [template](project/adr/0000-template.md) into `docs/<module>/adr/`, and add its row here in the same commit. A superseded ADR keeps its row and number; its status names the ADR that replaced it.

| ADR | Decision | Module | Status |
|---|---|---|---|
| 0001 | [Layers, modules and the direction of dependencies](project/adr/0001-layers-and-modules.md) | project | accepted |
| 0002 | [Testing strategy](project/adr/0002-testing-strategy.md) | project | accepted |
| 0003 | [Money as integer minor units](payroll/adr/0003-money-in-minor-units.md) | payroll | accepted |
| 0004 | [Identifiers](payroll/adr/0004-identifiers.md) | payroll | accepted |
| 0005 | [Aggregate state changes only through events](common/adr/0005-state-changes-through-events.md) | common | accepted |
| 0006 | [Freezing a line and recording ignored recalculations](payroll/adr/0006-freeze-and-ignored-recalculations.md) | payroll | accepted |
| 0007 | [A small event-sourcing core of our own](common/adr/0007-own-event-sourcing-core.md) | common | accepted |
| 0008 | [Optimistic concurrency on streams](common/adr/0008-optimistic-concurrency.md) | common | accepted |
| 0009 | [One command bus with a transaction and idempotency](common/adr/0009-one-command-bus.md) | common | accepted |
| 0010 | [Models for reading, built synchronously from events](common/adr/0010-synchronous-projections.md) | common | accepted |
| 0011 | [Drift is shown in the history, not decided in the line](payroll/adr/0011-drift-in-the-history.md) | payroll | accepted |
| 0012 | [Laravel as an adapter, inside app/](project/adr/0012-laravel-inside-app.md) | project | accepted |
| 0013 | [Event store and processed commands in MySQL](common/adr/0013-event-store-in-mysql.md) | common | accepted |
