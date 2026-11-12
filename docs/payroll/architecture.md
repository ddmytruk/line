# Payroll architecture

How the Payroll module is built, from the outside in: who uses an earning line, its domain model and what it guarantees. Modules, layers and quality checks of the whole repository are in the [architecture overview](../architecture.md).

## 1. Context

```mermaid
flowchart LR
    calc["Payroll calculation<br/>(external system)"] -- "calculated value<br/>of a line" --> el["Earning line"]
    spec(["Payroll specialist"]) -- "manual adjustment<br/>with a comment" --> el
    el -- "current value<br/>and history" --> spec
```

- **Payroll calculation** computes the value of a line from source data and sends it again whenever the source data changes. It is outside this project.
- **Payroll specialist** reviews lines and corrects them with manual adjustments.
- **Earning line** keeps the value, applies the rules and keeps the history.

## 2. Guarantees

What can go wrong, what prevents it and which test proves it. Rows are added in the step that introduces the guarantee.

| What can go wrong | Guarantee | Test |
|---|---|---|
| A sum of adjustments is off by a fraction of a cent | integer minor units, parsed from strings ([ADR 0003](adr/0003-money-in-minor-units.md)) | `MoneyTest::testAddsWithoutFloatingPointErrors` |
| An inexact or overflowing amount enters the history | `Money` rejects fractions of a cent and amounts out of range | `MoneyTest` |
| An adjustment is saved without a real explanation | `AdjustmentComment` rejects blank and whitespace-only comments | `AdjustmentCommentTest` |

## 3. Rules for the code

Rules of this module on top of the [AI workflow](../project/ai-workflow.md#rules-for-the-code):

- **No floats for money.** Amounts are parsed from strings into integer minor units.
- **Fail loudly on a broken history.** Replaying a line's events that contradict the rules is an error, never a silent repair.
