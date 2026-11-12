# Review of step 8: history read model

Scope: `Projector`, `ProjectingEventStore`, `ReadModelRebuild`, `EventStore::all()`, the line history with drift, its projector and in-memory store with contract tests, the `GetEarningLineHistory` query and its handler, the property test, ADR 0010 and 0011 and the architecture documents. The reviewer checked that the Payroll domain did not change and applied mutations to the projector, the store and the transaction order in a copy of the repository.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | A store that rolled back its streams but not the list of all events passed every test; a later rebuild would replay dropped events as phantom facts | accepted | the transaction test checks `all()` after a failure |
| 2 | The guarantee "projectors run in the transaction of the append" cited a test of a refused append only; a failing projector was never tested | accepted | test: a failing projector drops the events of its transaction; the guarantee cites it |
| 3 | A second first calculation of a line silently started its history again | accepted | the projector throws: the model is out of step and needs a rebuild |
| 4 | Tests cleared the histories before a rebuild, so a projector that did not reset passed | accepted | the clears are gone; a rebuild must forget a history without events |
| 5 | A rebuild that failed halfway left half-built models | accepted | `ReadModelRebuild` runs in a transaction; test of a failed rebuild |
| 6 | The read model holds domain `Money` | kept, explained | the application layer may use domain values; readers format them |
| 7 | The review record of the step was missing | accepted | this report and its row in the review index |
| 8 | ADR 0007 still counted two operations of the event store after `all()` was added | accepted | the ADR no longer counts them |
| 9 | ADR 0010 said a failing projector blocks every write, but it blocks the writes it fails on | accepted | ADR 0010 and the common architecture say so; ADR 0010 names when to revisit the decision |
| 10 | No test showed that the query reads the line by its canonical id | accepted | a test reads the history with an upper-case id |
| 11 | ADR 0010 makes `Query/` a folder of every module, while the folder rule names folders after what their classes do | accepted | ADR 0010 says what `Command/` and `Query/` hold: the requests that change a module and the requests that read it |
| 12 | `EarningLineNotFound` from the query would also be thrown for an existing line whose history is out of step | accepted | the handler says so; the cure is a rebuild |
| 13 | The read model kept the frozen flag of the previous history on a recalculation, so a stream the line would never produce gave a different history than before | accepted | each change states the flag it sets |
| 14 | The exception for a stored stream that breaks the rules had a name close to the read model `EarningLineHistory` | accepted | renamed to `CorruptedEarningLineEvents` |
| 15 | The methods of the projector shortened the names of the events, unlike `EarningLine::apply` | accepted | one method per event, named after the event |
