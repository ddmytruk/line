# 0001. Layers, modules and the direction of dependencies

- Status: accepted

## Context
Business rules are usually small, but a system stores, queries and exposes them through a framework and a database. If the rules depend on those tools, every change of storage or framework touches the rules, and the rules can no longer be tested on their own.

## Decision
- **Modules.** Each business area is a module (the first one is `Payroll`); what a module holds is described in its own documents. `Common` holds building blocks shared by any module and never depends on a business module.
- **Layers inside a module.** `Domain` (rules) lives in `src/<Module>`; an `Application` layer (use cases and ports) joins it there when a module needs one. Adapters that connect the core to the outside world, such as the command line, live in `app/Modules/<Module>`. Test doubles live in `tests/Support`, because only tests use them.
- **Dependencies point inwards.** Domain depends on nothing outside the domain layers. Application depends on the domain. Adapters depend on both. The core under `src/` never depends on a framework.
- **The rules are checked, not only described.** Architecture tests run with static analysis and fail the build on a wrong dependency.
- **Tests and documents follow the same modules** (`tests/<Module>`, `docs/<module>`), so everything about one module is in one place. Checks of the whole repository (architecture rules, runtime assumptions) sit at the root of `tests/`, and cross-cutting documents in `docs/project/`.

## Rejected alternatives
- **A single namespace with folders by technical type** (`Entities`, `Services`, `Repositories`): it hides which code belongs to which business area and does not prevent the domain from importing infrastructure.
- **A framework-first structure** (models and controllers from the start): the rules would be tied to the framework before there is any reason for it.

## Consequences
The domain can be tested and run with plain PHP. Adding persistence or a framework adds code around the domain without changing it. The cost is a few interfaces (ports) that a framework-first design would not need.
