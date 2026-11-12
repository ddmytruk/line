# 0014. Payroll on the database, the command line and HTTP

- Status: accepted

## Context
The Payroll module runs with in-memory adapters. On MySQL its events need stored names and a stored form, its history for reading needs a table, and the payroll calculation and specialists need a way in: artisan commands for people at a terminal and an HTTP API for other systems and screens.

## Decision
- **Stored events:** each event has a fixed type name, `payroll.<fact>`, written in `PayrollEventSerializer` and never derived from a class name. Amounts are stored as exact decimal strings with their currency, times as UTC with microseconds. Reading a stored event or history goes through the value objects of the domain; a stored value the domain refuses is reported as damaged data (a fault of the server, HTTP 500), never as a refusal of the current request, and never repaired.
- **History for reading:** one row per line in `earning_line_histories`, with the whole history as a JSON document. A history is only ever read whole and by line id; columns for filtering are added when a query needs them. The document can always be built again from the events (`payroll:rebuild`).
- **Wiring:** `PayrollServiceProvider` builds the same objects as the in-memory module, on the stores of the database: one command bus with the Payroll handlers, the projector of the history behind the event store, the rebuild and the query handler. The stores, the transaction and the clock come from `CommonServiceProvider`. A web server sends HTTP requests to `app/public/index.php`.
- **Artisan commands:** `payroll:record-value`, `payroll:adjust` (with `--expected-version` and `--specialist`), `payroll:history` and `payroll:rebuild`. A command id is given with `--command-id` to repeat a request safely; without it every call is a new request. A refusal prints its reason and exits with 1; a missing `--expected-version` or `--specialist` exits with 2.
- **HTTP API:**

  | Request | Body | Answer |
  |---|---|---|
  | `POST /lines/{id}/calculations` | `commandId`, `amount`, `currency` | 204 |
  | `POST /lines/{id}/adjustments` | `commandId`, `expectedVersion`, `amount`, `currency`, `comment`, `specialistId` | 204 |
  | `GET /lines/{id}/history` | | 200 with the history as JSON, including the version to send back |

  A request repeated under the same `commandId` answers 204 and changes nothing. 404: the line does not exist. 409: the line changed since the version the specialist saw, or the `commandId` was used for other content. 422: a field is missing or has the wrong type (an empty `commandId`, a negative `expectedVersion`, a body that is not JSON), or the request breaks a rule of the domain. Every error, including a fault, is answered as JSON. Amounts must be JSON strings: a JSON number would reach the server as a float and lose exactness.
- The sender chooses the command id and states the specialist (open questions Q15 and Q17).

## Rejected alternatives
- **Class names as stored type names:** renaming or moving an event class would make stored events unreadable.
- **Columns for every field of the history:** a schema to migrate with every change of the model for reading, for queries that do not exist yet.
- **Laravel validation and form requests:** a failed validation redirects a client that does not ask for JSON, and it checks types loosely; a few typed checks are shorter and give one error format.
- **Returning the new history from a command:** it mixes writing and reading; the client reads the history with `GET` when it needs it.

## Consequences
Both ways in send the same commands to the same bus, so they share every rule, the protection against repeats and the conflict on an outdated version. A stored type name is a promise: renaming one needs a migration of stored events. A negative amount on the command line is written after `--`, so it is not read as an option.

`payroll:rebuild` is meant to run while no commands are sent: a command that read a history before the rebuild cleared it writes that history plus its own fact over the rebuilt one. The result is still right when the old history was right; after a fix of the projector, writers are stopped for the rebuild.
