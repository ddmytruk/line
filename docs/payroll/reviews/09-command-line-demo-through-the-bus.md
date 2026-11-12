# Review of step 9: command-line demo through the bus and release 1.1.0

Scope: the demo command, its clock and history view, the in-memory wiring of the module, `bin/earning-line`, the README, the changelog and the plan. Because this is the last step before the 1.1.0 tag, the reviewer also checked the whole release: every relative link and anchor, every test the documents name, that the README example matches the real output, that the Payroll domain did not change since 1.0.0, and that CI and the Makefile run the new code.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The review record of this step was missing, while the README says every step was reviewed | accepted | this report and its rows in the review index |
| 2 | The log fell back to the calculated value when an ignored recalculation had no drift, which would print a wrong number as drift | accepted | the view throws instead |
| 3 | The demo's rebuild compared the history with itself, so a rebuild that did nothing would still be reported as equal | accepted | the demo drops the history before the rebuild |
| 4 | The help text of `make demo` did not mention the command bus | accepted | aligned with `bin/earning-line` |
| 5 | The README placed every port directly in `src/*/Application`, while some ports are in folders of that layer | accepted | the README says "and its folders" |
| 6 | The module exposes the store of histories, which reads like a second way to read them | accepted | a comment says it is there to drop the histories before a rebuild; reading goes through `getEarningLineHistory` |
