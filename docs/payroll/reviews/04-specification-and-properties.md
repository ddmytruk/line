# Review of step 4: executable specification and property-based tests

Scope: `ReferenceScenarioTest`, the random action generator and `EarningLinePropertiesTest`. The reviewer applied about twenty mutations to `EarningLine` in a copy of the repository and recorded which tests killed them.

Result: the property-based test killed every mutation of the rules (freezing the first value, unfreezing at a zero sum, wrong deduplication, ignored recalculations not recorded or recorded with the wrong value, numbering errors). The scenario test missed the mutations that only affect the recorded log.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The scenario test did not check the full log it is documented to check | accepted | the log is compared fact by fact, including the ignored recalculation |
| 2 | The replay seed was lost when the domain threw instead of failing an assertion | accepted | any error is rethrown with the seed |
| 3 | `PROPERTY_SEED` did not reach the container, so `make test` silently ignored it | accepted | passed through `docker/compose.yaml` |
| 4 | The generator never repeated the first value or returned to an earlier one such as the frozen value | accepted | values sent earlier are reused on purpose |
| 5 | Step 4 was not tried with the frozen value itself, which is the one value that is not recorded | accepted | added to the scenario |
| 6 | The exact-sum scenario test repeated `MoneyTest` | accepted | removed |
| 7 | A non-numeric seed silently became 0 | accepted | fails loudly |
| 8 | The model checked how many facts were recorded, not which | accepted | the expected sequence of fact types is compared |
