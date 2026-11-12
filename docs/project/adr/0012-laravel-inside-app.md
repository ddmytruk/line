# 0012. Laravel as an adapter, inside app/

- Status: accepted

## Context
The core in `src/` is plain PHP and reaches storage and time only through ports. Running it as a real application needs a framework for the database, the command line and HTTP. A framework placed at the root of the repository would sit next to the core and invite its classes, helpers and conventions into it.

## Decision
- The Laravel application lives in `app/`: `app/` is its base path, with `artisan`, `bootstrap/` and `storage/` there. The core, the tests and the documents stay at the root.
- Each module keeps its Laravel code in `app/Modules/<Module>`: its service provider and the adapters of its ports. `bootstrap/providers.php` lists the providers of the modules; the application has no code of its own outside them.
- Folders in a module are named after what their classes do, as everywhere else.
- `src/` never depends on Laravel, and neither do the in-memory adapters and the command-line demo of `app/Modules/*/InMemory` and `app/Modules/*/Cli`; phpat checks both. The demo without a framework keeps working.
- The database is MySQL. Tests that need it form their own suite (`make test-integration`, a separate CI job); the other checks run without a database, and MySQL starts only for the commands that need it.

## Rejected alternatives
- **The standard Laravel layout at the root:** familiar, but `app/`, `config/`, `database/` and `routes/` of the framework would mix with the core, and nothing would show where a module ends.
- **Only the database component of Laravel, without the application:** fewer packages, but commands, HTTP, configuration and migrations would have to be wired by hand.
- **Tests against SQLite in memory as well:** a faster cycle, but SQL differences between two databases to keep, with nothing new to show; the ports and their contract tests already show that another database is one more adapter.

## Consequences
Laravel is replaceable in the same way as storage: a module without its `app/Modules/<Module>` folder still works with in-memory adapters. Laravel's defaults that expect `app/` to be the code folder are set once in `bootstrap/app.php`. Laravel cannot detect an application namespace here (it looks for `app/composer.json`), so its code generators (`make:*`) are not used; classes are written by hand in their module. A test that needs the database is slower and runs in its own suite.
