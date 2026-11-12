# Reviews

Every review of the repository, in one list. A review lives in the folder of the module its step is about, but its number is the number of the step in the [plan](project/plan.md), so the numbers are shared by the whole repository and this list shows which step was reviewed where.

A review is added in the commit of the step it reviews, together with its row here: `NN-<step>.md` in `docs/<module>/reviews/`, where `NN` is the step number. Not taken means rejected or kept with an explanation; the review says why.

| Step | Review | Module | Findings | Accepted | Deferred | Not taken |
|---|---|---|---|---|---|---|
| 0 | [Plan and rules](project/reviews/00-plan-and-rules.md) | project | 10 | 10 | 0 | 0 |

## What the AI got wrong and how it was caught

What the reviews caught that the implementing agent got wrong: one case per step that shows best what a review is for.

| Step | Where | What the AI produced | Why it is a problem | How it was caught | What changed |
|---|---|---|---|---|---|
| 0 | `docs/payroll/domain.md` | Domain rules without the data each recorded fact carries | Storing or presenting events could later need new fields, so the domain would have to change | [Review of step 0](project/reviews/00-plan-and-rules.md): plan checked against the domain document | "Recorded facts" section with the data of every fact |
