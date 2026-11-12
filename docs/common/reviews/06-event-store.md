# Review of step 6: event store and repository

Scope: the `EventStore` port, `ConcurrencyConflict`, the in-memory event store and its contract tests, the `EarningLines` repository, ADR 0007 and 0008, the architecture documents and the plan. The reviewer checked that the Payroll domain did not change, reproduced a lost update with a throwaway script and applied mutations to the in-memory store in a copy of the repository.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | After a failed save the line object has given away its new events; saving it again appended nothing and reported success, so an adjustment was lost silently | accepted | the repository refuses to save a line whose save failed; test and ADR 0008 |
| 2 | A store that ignores the version of a stream that does not exist passed every contract test | accepted | contract case: a version above zero for a missing stream is a conflict and writes nothing |
| 3 | ADR 0007 said the in-memory store is used by the command line, which does not use it yet, and counted two interfaces | accepted | wording matches the code of the step |
| 4 | Whether an empty append checks the version was not specified | accepted | an empty append changes and checks nothing; port docblock and contract |
| 5 | All-or-none and the aggregate-id check were tested only on a new stream | accepted | the same case on an existing stream |
| 6 | The plan described in-memory storage as the state of the command line | accepted | phrased as the scope of the release |
| 7 | The architecture overview states a rule for the application layer that no architecture test names | accepted | explicit phpat rule for the application layers |
| 8 | The folder rule put shared ports in the root of a layer, but the event store port sat alone in a folder, and the root of the Payroll application layer holds a repository | accepted | `EventStore` is in the root; the repository, which serves only changing a line, is in `Command/`, the part of the layer that changes the module |
| 9 | The exception rule accepted any namespace that ends in `Exception`, and the overview did not say that it covers only `src/` | accepted | the rule matches the `Exception/` folder of a domain or application layer exactly; the overview says it checks `src/` |
