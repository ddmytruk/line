# Documentation

Documents are grouped the same way as the code: by module, plus a `project` folder for everything that spans the whole repository.

| Folder | Contents |
|---|---|
| [`architecture.md`](architecture.md) | Overview of the repository: modules, layers, quality checks |
| [`decisions.md`](decisions.md) | Index of all ADRs: number, module and status |
| [`reviews.md`](reviews.md) | Index of all step reviews: step, module and findings |
| `project/` | [Plan](project/plan.md), [git workflow](project/git-workflow.md), [AI workflow](project/ai-workflow.md), cross-cutting ADRs (`project/adr/`) and reviews (`project/reviews/`) |
| `common/` | Shared core: building blocks for any module, such as the aggregate root and the event store: [architecture](common/architecture.md), ADRs (`common/adr/`) and reviews (`common/reviews/`) |
| `payroll/` | [Domain: rules, lifecycle, edge cases, open questions](payroll/domain.md), [architecture: context, domain model, guarantees](payroll/architecture.md), ADRs (`payroll/adr/`) and reviews (`payroll/reviews/`) |

A folder appears with its first document. ADRs are numbered across the whole repository (0001, 0002, ...) and live in the folder of the module they are about. The [decision index](decisions.md) lists all of them and gives the next free number. The template is [`project/adr/0000-template.md`](project/adr/0000-template.md). Reviews are numbered by the step they review and listed in the [review index](reviews.md).
