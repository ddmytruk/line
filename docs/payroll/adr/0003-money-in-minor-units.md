# 0003. Money as integer minor units

- Status: accepted

## Context
Pay amounts are decimal, but binary floating point cannot store 0.10 or 0.20 exactly: `0.1 + 0.2` is `0.30000000000000004`. A sum of adjustments, a comparison or a difference computed with floats can be off by a fraction of a cent, and an audit history must add up exactly.

## Decision
- `Money` holds an integer number of minor units (cents) and a `Currency`.
- Amounts are parsed from decimal strings with at most two decimal places. A fraction of a cent is rejected, not rounded.
- Arithmetic is allowed only within one currency; mixing currencies throws `CurrencyMismatch`.
- The range is ±(10^15 − 1) minor units. A result outside the range throws, so an overflow can never be recorded.
- Supported currencies all have two decimal places (open question Q6).
- Formatting for people (symbols, thousands separators) belongs to the presentation layer, not to `Money`.

## Rejected alternatives
- **`float`:** inexact by design, see the context.
- **`bcmath` strings or a money library:** exact, but an extension or a dependency for two operations (add and subtract) that integers do exactly.
- **Rounding a fraction of a cent:** the history would hold an amount nobody entered.

## Consequences
All arithmetic is exact and fast. The difference of two amounts in range can itself be out of range, so code that records a value it will later compare with another must check the difference before recording it. Adding a two-decimal currency is one new enum case in the domain. A currency with other decimal places needs a precision per currency in `Money` parsing and formatting; that is a deliberate domain change, not configuration.
