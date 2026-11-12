# Review of step 0: plan and rules

Scope: the plan, workflows, ADR 0001, the Payroll domain document and the architecture overview. No code yet, so the review checked consistency between documents, links, and whether the rules and the lifecycle are complete enough to implement without changing the domain later.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The documentation index links folders that do not exist yet | accepted | folders are named as paths; a folder appears with its first document |
| 2 | The reference scenario is used by the plan but not written down | accepted | "Reference scenario" section in the domain document |
| 3 | The plan says every out-of-scope item has an open question, but technical topics do not | accepted | out of scope split into business decisions (open questions) and technical topics |
| 4 | The data of recorded facts is not fixed, so storing or presenting them could later need new fields in domain events | accepted | "Recorded facts" section: each fact with its data; times are passed in by the caller |
| 5 | Rule 4 does not say that ignored recalculations are kept; drift is not defined | accepted | rule 4 reworded; drift added to the terms |
| 6 | ADR 0001 does not say where the command-line adapter and test doubles live | accepted | adapters in `app/Modules/<Module>`; test doubles in `tests/Support` |
| 7 | "`make qa` passes on any commit" cannot hold before the skeleton exists | accepted | stated from the skeleton step on |
| 8 | Commit scopes do not match the modules | accepted | the scope is the module |
| 9 | Missing open questions: out-of-order recalculations, same-value recalculation, currency of a recalculation | accepted | Q11, Q12, Q6 extended |
| 10 | Q6 refers to an implementation detail | accepted | phrased in business terms |
