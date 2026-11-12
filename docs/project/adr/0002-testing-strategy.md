# 0002. Testing strategy

- Status: accepted

## Context
The value of the code is in its business rules. A broken rule is the failure that matters, while getters and wiring rarely break. Tests should prove the rules, run in seconds and stay readable as documentation.

## Decision
| Kind | Proves | Where |
|---|---|---|
| Unit tests | value objects and the aggregate, one rule per test | `tests/<Module>/Unit` |
| Executable specification | the reference scenario of a module, every step and its final result | `tests/<Module>/Scenario` |
| Property-based tests | invariants hold for hundreds of random sequences of actions, not only for the chosen examples | `tests/Payroll/Unit` |
| Architecture rules | the dependency rules of ADR 0001 | `tests/Architecture`, run by phpat inside PHPStan |

- Property-based tests use a small generator in `tests/Support` with a fixed seed, printed on failure, instead of a library.
- Tests run in random order to catch hidden coupling between them.
- When the code gets ports and adapters, every implementation of a port runs the same contract tests, and adapters are tested against the real technology they wrap.
- There is no line-coverage target. Coverage shows what is not tested, not what is tested well; the reviews of this repository check assertion strength with deliberate mutations of the code, and from release 1.2.0 Infection mutates the core on every CI run with a minimum score.
- PHPStan runs at the maximum level with strict rules, so type errors are caught before tests run.

## Rejected alternatives
- **A property-testing library:** one more dependency for about fifty lines of generator code, and it ties the test suite to the library's PHPUnit support.
- **A 100% coverage goal:** it rewards tests of trivial code and says nothing about the quality of assertions.
- **Deptrac for architecture rules:** a separate tool and configuration; phpat expresses the same rules as PHP code and runs with static analysis.

## Consequences
`make qa` runs everything: coding style, static analysis with architecture rules, all tests. It takes seconds, so it runs before every commit.
