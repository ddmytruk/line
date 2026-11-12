# Review of step 1: skeleton

Scope: Composer, PHPUnit, PHPStan with phpat, PHP CS Fixer, Docker, Makefile, CI and ADR 0002. The reviewer ran all checks on a clean export of the commit and probed the architecture rules with deliberately wrong classes outside the repository.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The domain rule was a deny list: a dependency on any library other than the listed namespaces (for example a test framework or a date library) passed unnoticed | accepted | the domain and the core may depend only on themselves and standard PHP classes; checked with a planted violation |
| 2 | On a Linux host the container ran as root, so `vendor/`, caches and fixed files became root-owned | accepted | the service runs as the host user; Composer gets a writable home |
| 3 | `Tests` was listed among adapters and reported twice | accepted | covered by the allow lists |
| 4 | CI never builds the Docker image and does not validate `composer.json` | accepted | `composer validate --strict` and an image build job |
| 5 | "CI runs the same checks" did not say how | accepted | wording in the architecture overview |
| 6 | The PHP version test repeats Composer's platform check | accepted | removed; the 64-bit check stays |
| 7 | Root-level tests are outside the `tests/<Module>` layout without a note | accepted | ADR 0001 explains where repository-wide checks live |
| 8 | `ext-mbstring` is required before code uses it | rejected | it ships with every PHP build in use, and the planned value objects need it; moving it would only churn the lock file |
| 9 | Found at the release checkpoint, after the review: the lock file was resolved with the PHP platform check switched off, so `make install` failed on a clean machine. Development tools need PHP 8.4.1, the platform was pinned to 8.4.0 | accepted | the PHP constraint and the platform are 8.4.1; the install is checked in a clean container without overrides |
