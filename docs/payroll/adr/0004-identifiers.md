# 0004. Identifiers

- Status: accepted

## Context
A line is identified before it is stored, by whoever creates it. The author of an adjustment is identified by another system (the user directory). Both ids end up in an append-only history, so a malformed id cannot be fixed later.

## Decision
| Id | Format | Why |
|---|---|---|
| `EarningLineId` | UUID in canonical lower case | known before storage, unique across systems, does not reveal how many lines exist; one canonical spelling, so the same line is never stored under two ids |
| `SpecialistId` | opaque string of 1 to 64 visible characters without control characters | the user directory owns the format; Payroll only refers to it, but the reference must stay readable in the history |

UUID version 7 is recommended for new lines, because it is ordered by time and keeps database indexes compact, but any UUID is accepted.

## Rejected alternatives
- **Auto-increment integers:** the id would exist only after storage, and it leaks volumes.
- **A UUID library:** a regular expression validates the format; generation is needed only at the edges.
- **Imposing a format on `SpecialistId`:** it would couple Payroll to the user directory.

## Consequences
Ids are validated once, at construction, so the rest of the code can trust them.
