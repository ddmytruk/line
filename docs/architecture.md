# Architecture

How the repository is built: modules, layers and the checks that keep them apart. Every step of the [plan](project/plan.md) updates the architecture documents together with the code.

## 1. Modules and layers

```mermaid
flowchart TB
    subgraph src["src/ (plain PHP, no framework)"]
        direction TB
        subgraph payroll["Payroll"]
            pd["Domain<br/>rules of an earning line"]
        end
        subgraph common["Common"]
            cd["Domain<br/>aggregate root"]
        end
    end
    pd --> cd
```

Arrows point in the direction of dependencies. The rules of [ADR 0001](project/adr/0001-layers-and-modules.md) apply:

| Rule | Why |
|---|---|
| Domain depends on nothing outside domain layers | business rules are testable on their own |
| `Common` never depends on `Payroll` | shared building blocks can serve any module |
| `src/` never depends on a framework | storage and framework are replaceable tools |

Each module describes its own architecture next to its other documents: [Payroll](payroll/architecture.md) (context, domain model, guarantees).
