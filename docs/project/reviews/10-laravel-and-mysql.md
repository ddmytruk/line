# Review of step 10: Laravel and MySQL

Scope: the Laravel application in `app/`, MySQL in Docker Compose and CI, the integration suite, the architecture rule for the in-memory adapters and the demo, and ADR 0012. The reviewer ran all checks, ran the integration suite against MySQL with the local database name set from outside, and probed the new architecture rule with a planted Laravel dependency on a copy of the repository.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The quick start said `make qa` runs all tests, but it runs only the tests that need no database | accepted | the quick start names `make test-integration` too |
| 2 | The `app` service waited for MySQL, so every `make` command started a database it does not use, against ADR 0012 | accepted | MySQL starts only in the targets that need it |
| 3 | ADR 0012 named triggers and migrations that do not exist yet | accepted | the ADR describes only what the step has |
| 4 | CI and `make migrate` ran migrations that do not exist yet | accepted | removed; they come with the first table |
| 5 | Laravel cannot detect an application namespace in `app/`, so its code generators fail | accepted | ADR 0012 records that generators are not used |
| 6 | The test database is set both as an environment variable and as a server variable without saying why; removing one as a duplicate would let tests reach the local database | accepted | comment in `phpunit.xml.dist` |
| 7 | Without `DB_CONNECTION` Laravel falls back to SQLite | accepted | the test configuration forces MySQL as well |
| 8 | `storage/framework/cache` has no ignore file of its own | accepted | added |
| 9 | `ext-pdo_mysql` is not declared in `composer.json` | deferred | declared together with the first database adapter in step 11 |
| 10 | The review record and its row in the list of reviews were missing | accepted | this record |
