# Review of step 13: mutation testing and release 1.2.0

Scope: mutation testing of the core with Infection, its thresholds and CI job, the tests that kill the mutants a first run let survive, the README and the changelog of release 1.2.0. Infection needs a coverage driver that the environment of the implementing agent did not have, so the project owner ran it: 228 of 238 mutants killed, every line covered. The reviewer applied every surviving mutant by hand, ran the commands of the README against MySQL and served the API.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | One mutant declared harmless was not: without its currency check, a zero adjustment in another currency was refused as a zero adjustment instead of for its currency | accepted | a test for it; five survivors remain, each explained in the architecture overview |
| 2 | With a gate of 97 %, one new survivor still passed, although the configuration said it would fail | accepted | the gate is 97.8 %: 233 of 238 passes, 232 fails |
| 3 | `migrate` in the quick start asks for confirmation outside a local environment, so the commands after it found no tables | accepted | `migrate --force` |
| 4 | The commit was marked as work in progress | accepted | the commit message of the step |
| 5 | The mutation job installed the MySQL driver it does not need | accepted | removed |
| 6 | pcov was installed without a version | accepted | pinned to 1.0.12 |
| 7 | An existing Docker image has no pcov, and nothing said so | accepted | the changelog says to run `make build` |
| 8 | ADR 0002 still said mutations are only checked by hand in reviews | accepted | it names the mutation testing in CI |
| 9 | Found by the project owner at the release check: `make serve` fails when port 8000 of the host is taken | accepted | `make serve PORT=8080` |
| 10 | Found by the project owner at the release check: MySQL of Docker Compose cannot be opened from a database client on the host | accepted | published on `127.0.0.1:3307`, not 3306, so it does not clash with a MySQL of the host; `MYSQL_PORT` for another port |
