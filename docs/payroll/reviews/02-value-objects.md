# Review of step 2: value objects

Scope: `Money`, `Currency`, `AdjustmentComment`, `EarningLineId`, `SpecialistId`, ADR 0003 and 0004, edge cases in the domain document. The reviewer probed parsing, ranges and Unicode handling with throwaway scripts on PHP 8.4, and checked that the API is enough for the aggregate and the later read model without changing these classes.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | The difference of two in-range amounts can exceed the range, so drift of a stored recalculation could fail to compute | accepted | ADR 0003 states it: whoever records a value that will be compared with another must check that the difference is in range before recording |
| 2 | `SpecialistId` accepted invisible characters and control characters, which could never be corrected in the history | accepted | the same invisible-character trimming as comments (`VisibleText`); control characters rejected |
| 3 | A comment made only of control characters or blank fillers counted as an explanation | accepted | such characters are invisible; control characters inside the text are rejected, tabs and line breaks kept |
| 4 | The documented floating-point case (−0.10 + −0.20) was not the case the test checked | accepted | the test checks both signs |
| 5 | The documents called a new currency "configuration", while it is a domain enum case | accepted | ADR 0003 and Q6 say it plainly |
| 6 | Lenient currency parsing of user input is an edge concern and threw a money error | accepted | removed from the domain; the command line parses input |
| 7 | Error messages copied raw input, including invalid UTF-8, although they are meant for users | accepted | messages no longer echo input; a separate message for unreadable comment text |
| 8 | The 13-digit parsing limit, `MAX + 1`, `-0` and padded UUIDs were not tested | accepted | tests added |
| 9 | The range was described in dollars, while lines can be in other currencies | accepted | described in units of currency |
