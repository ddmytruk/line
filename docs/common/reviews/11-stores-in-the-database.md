# Review of step 11: stores in the database

Scope: the event store, the processed commands and the transaction in MySQL, their migrations and triggers, the service provider of `Common`, the transaction contract and the tests against MySQL, ADR 0013. The reviewer ran the CI path on a fresh MySQL container, ran six processes appending at once (one busy stream, the same new streams, random rollbacks) and found no gaps or duplicates in any stream, and checked that removing the version check, the unique key, its handling or the order of `all()` each fails a test.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | Ids used MySQL's default collation, so ids differing only in letter case, accents or trailing spaces were the same id in MySQL but different ids in memory; a sender's `Cmd-1` after `cmd-1` would be refused as a reused id | accepted | ids and type names compare byte for byte; both contracts test ids that differ only in case or trailing spaces |
| 2 | A MySQL volume created before this step never runs the new line of `init.sql`, so the migrations fail to create triggers | accepted | Docker Compose sets the option when MySQL starts; CI keeps `init.sql` |
| 3 | Under load, racing writers with a rollback deadlock in about one attempt in a hundred, and the bus does not repeat a deadlock | deferred | recorded in ADR 0013; treating a deadlock as a conflict needs a check of Laravel's count of nested transactions |
| 4 | The tests recreate the tables before checking which database they reached | accepted | they refuse to run on any database other than the test one |
| 5 | JSON does not keep the form of numbers or the order of keys | accepted | stated in `EventSerializer` |
| 6 | Length limits of ids and fingerprints exist only in MySQL | accepted | stated in ADR 0013 |
| 7 | Reading all events page by page was never tested beyond one page | accepted | the page size is a parameter; a test reads five events in pages of two |
| 8 | Nothing resolves the event store from the container, because no module provides a serializer yet | deferred | checked when the first module provides its serializer in step 12 |
