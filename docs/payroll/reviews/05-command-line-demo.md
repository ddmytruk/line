# Review of step 5: command-line demo and release 1.0.0

Scope: the demo command, its view of the history, `bin/earning-line`, the README and the changelog. Because this is the last step before the 1.0.0 tag, the reviewer also checked the whole repository: every relative link in every document, that the documents match the code, that everything is in English, and that the demo runs as the host user, the way `make demo` runs it.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | Review reports for steps 4 and 5 were missing, while the README says every step was reviewed | accepted | reports added before the tag |
| 2 | An unknown command printed only the usage | accepted | `Unknown command "..."` on standard error, exit code 1 |
| 3 | CI never ran the entry point, so a broken autoload mapping would pass | accepted | CI runs the demo |
| 4 | A comment in the demo named a test, outside its layer | accepted | removed |
| 5 | `composer.json` declared a license without a license file | accepted | marked proprietary until the owner chooses a license |
| 6 | The changelog has no version links | deferred | added once the public repository address exists |

Confirmed: README output matches the real output; all links resolve; no change under `src/` in this step.
