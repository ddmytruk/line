# Review of step 7: commands and the command bus

Scope: `CommandBus`, the ports `Transaction`, `ProcessedCommands` and `Clock`, the in-memory transaction and store of processed commands with their contract tests, the Payroll commands and handlers, the reference scenario through the bus, ADR 0009 and the architecture documents. The reviewer checked that the Payroll domain did not change and applied six mutations to the bus and the transaction in a copy of the repository; five were caught.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | A bus that swallowed `CommandAlreadyProcessed` without running the command again passed every test; a sender whose id was taken by a run with other content would be told "done" | accepted | test with a racing run of other content: the sender gets `CommandIdReused` |
| 2 | A copy of a safe command carried out during its retry reaches the sender as `CommandAlreadyProcessed`, which was not documented | accepted | `@throws` of the bus and ADR 0009 |
| 3 | "The only way to change the system" overclaims: the repository can be used directly, and no rule enforces it | accepted | wording: commands from senders are carried out by the bus |
| 4 | The overview's summary of the module documents missed the new sections | accepted | summary updated |
| 5 | A test named "records nothing" ended at the exception and never checked it | accepted | the test asserts that nothing was recorded |
| 6 | The claim that a refused command can be corrected and sent under the same id was not tested | accepted | tests for the bus and for an adjustment |
| 7 | The fingerprint depends on the class and its properties, which matters once ids are stored across releases | accepted | noted in ADR 0009 |
| 8 | A nested in-memory transaction behaves like a savepoint | accepted | stated in the class docblock |
| 9 | ADR 0009 named the bus by a namespace that does not exist | accepted | the full name, in the `Command` namespace |
| 10 | Two exceptions imported `Command` without using it; the coding style checks do not report unused imports | accepted | imports removed |
| 11 | No test showed that a command turns its own currency code into money: a `money()` that ignored the code would pass | accepted | tests: an adjustment in an unknown currency and in another currency than the line, and a line recorded in EUR |
| 12 | Nothing in the step said where commands, handlers and the repository live | accepted | the Commands section of the Payroll architecture names `Application/Command/` |
