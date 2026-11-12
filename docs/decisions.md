# Architecture decisions

Every ADR of the repository, in one list. An ADR lives in the folder of the module it is about, but the number is shared by the whole repository, so this list is where the next number comes from.

To add an ADR: take the next number after the last row, copy the [template](project/adr/0000-template.md) into `docs/<module>/adr/`, and add its row here in the same commit. A superseded ADR keeps its row and number; its status names the ADR that replaced it.

| ADR | Decision | Module | Status |
|---|---|---|---|
| 0001 | [Layers, modules and the direction of dependencies](project/adr/0001-layers-and-modules.md) | project | accepted |
