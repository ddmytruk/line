# Reviews

Every review of the repository, in one list. A review lives in the folder of the module its step is about, but its number is the number of the step in the [plan](project/plan.md), so the numbers are shared by the whole repository and this list shows which step was reviewed where.

A review is added in the commit of the step it reviews, together with its row here: `NN-<step>.md` in `docs/<module>/reviews/`, where `NN` is the step number. Not taken means rejected or kept with an explanation; the review says why.

| Step | Review | Module | Findings | Accepted | Deferred | Not taken |
|---|---|---|---|---|---|---|
| 0 | [Plan and rules](project/reviews/00-plan-and-rules.md) | project | 10 | 10 | 0 | 0 |
| 1 | [Skeleton](project/reviews/01-skeleton.md) | project | 9 | 8 | 0 | 1 |
| 2 | [Value objects](payroll/reviews/02-value-objects.md) | payroll | 9 | 9 | 0 | 0 |
| 3 | [Aggregate](payroll/reviews/03-aggregate.md) | payroll | 9 | 8 | 0 | 1 |

## What the AI got wrong and how it was caught

What the reviews caught that the implementing agent got wrong: one case per step that shows best what a review is for.

| Step | Where | What the AI produced | Why it is a problem | How it was caught | What changed |
|---|---|---|---|---|---|
| 0 | `docs/payroll/domain.md` | Domain rules without the data each recorded fact carries | Storing or presenting events could later need new fields, so the domain would have to change | [Review of step 0](project/reviews/00-plan-and-rules.md): plan checked against the domain document | "Recorded facts" section with the data of every fact |
| 1 | `tests/Architecture/LayerRules.php` | Architecture rules as a short deny list of forbidden namespaces | Any other library could enter the domain unnoticed, which is the dependency that would later force domain changes | [Review of step 1](project/reviews/01-skeleton.md): the reviewer planted a domain class that used a test framework, and the rules did not report it | Allow lists: the domain may depend only on itself and standard PHP classes |
| 2 | `SpecialistId` | Plain `trim()` for the author id | An invisible or multi-line author could be written into an append-only history and never corrected | [Review of step 2](payroll/reviews/02-value-objects.md): Unicode probes on PHP 8.4 | `VisibleText` trimming shared with comments; control characters rejected |
| 3 | `EarningLineTest` | Time-zone test only for the first event, other paths compared with `assertEquals` | `assertEquals` ignores the time zone of dates, so local times could be stored unnoticed | [Review of step 3](payroll/reviews/03-aggregate.md): mutation probe removing the UTC conversion survived | Formatted times checked for every kind of fact |
