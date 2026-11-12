# Review of step 3: aggregate

Scope: `AggregateRoot`, `DomainEvent`, `EarningLine`, its events, ADR 0005 and 0006, diagrams and edge cases. The reviewer ran mutation probes on a copy of the repository and checked that storing, rebuilding and presenting a line needs nothing beyond the domain's public API.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The domain model section split the layers diagram from its explanation | accepted | section moved after the layer rules |
| 2 | UTC was proven only for the first calculation: the comparison of dates in tests ignores the time zone | accepted | the test checks the formatted time of every kind of fact |
| 3 | Guards in the command methods are repeated by `apply()`, so no test can tell them apart | kept, explained | rules are decided before recording; `apply()` repeats them only to refuse a corrupted history |
| 4 | Replay of an ignored recalculation in another currency was not tested | accepted | currency cases for every kind of fact |
| 5 | Replay fails with different exception types, and a domain exception is meant for users | accepted | ADR 0005: loading code treats any replay failure as a corrupted history |
| 6 | Events are rebuilt through validating constructors, so tightening a limit would stop stored history from loading | accepted | ADR 0005 and Q9: limits are only relaxed in the domain; stricter input rules go to the application layer |
| 7 | The drift range check was not documented | accepted | edge case and sequence diagram |
| 8 | Replay accepts a repeated value that the write path would skip | accepted as is | documented: replay checks rules that protect money and order |
| 9 | The class diagram missed `id()` and `aggregateId()` | accepted | added |

Confirmed: everything needed to store and rebuild a line is public (`reconstitute`, `version`, `releaseEvents`, readonly event data), so persistence can be added without changing the domain.
