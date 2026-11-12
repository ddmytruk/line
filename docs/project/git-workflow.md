# Git workflow

## Rules

- **One `main` branch with a linear history.** Every commit is one step of the [plan](plan.md), so the history reads as the story of the project.
- **A commit is a delivered step.** It contains everything of that step: code and tests, the ADR of a decision taken in it (and its row in the [decision index](../decisions.md)), a `CHANGELOG.md` entry under `[Unreleased]` and the matching update of the architecture documents (the [overview](../architecture.md) and the module's `architecture.md`).
- **Every commit is green.** From the skeleton step on, `make qa` passes on any commit, so the history is safe to check out and to `git bisect`.
- **[Conventional Commits](https://www.conventionalcommits.org/).** Types `feat`, `test`, `fix`, `docs`, `build`, `chore`; the scope is the module (`common`, `payroll`), and the subject names the layer when it matters. The message states what was done in a few lines. Why, and what was rejected, belongs to the ADR.
- **Tags mark releases** (`v1.0.0`); on a tag `[Unreleased]` in the changelog becomes the version.

## Review is part of the step

A code review is not a separate deliverable. It completes the step it reviews:

1. The step is committed.
2. A reviewer looks at the diff **and its impact zone**: callers of the changed code, the ports it implements, the tests that cover it, the documents that describe it.
3. Each accepted finding becomes a `git commit --fixup=<step>` commit.
4. `git rebase --autosquash` folds the fixups into the step, so the history keeps one commit per step.
5. What the review found, how it was caught and what changed is recorded in `docs/<module>/reviews/` and listed in the [review index](../reviews.md) inside the same commit, so the review stays visible after the squash.

A finding about an earlier step is a fixup of that earlier step. **History that is already tagged is never rewritten:** a problem found after a release is fixed by a regular `fix(scope)` commit in the next one.

## Why not something else

- **GitFlow** (`develop`, `release/*`, `hotfix/*`) serves teams with several releases in flight. Here it would only add ceremony.
- **Feature branches with pull requests** add nothing without a second human reviewer. In a team, each step would be a pull request into `main` (GitHub flow), and CI already runs on pull requests.
