# 0013. Event store and processed commands in MySQL

- Status: accepted

## Context
The event store, the processed commands and the transaction are ports with in-memory adapters. Stored in MySQL, they must keep the same guarantees when several processes write at once, and events, once stored, are facts that nobody changes.

## Decision
- **Events** are rows of the `events` table: `position` (auto-increment, the order of `EventStore::all()`), `stream_id`, `stream_version` (the number of the event in its stream, from 1), `event_type` and `payload` (JSON). The pair of stream and version is a unique key. Ids and type names compare byte for byte (`utf8mb4_0900_bin`), as in memory: MySQL's default collation would treat ids that differ in letter case, accents or trailing spaces as the same.
- **Append** reads the version of the stream and refuses a different expected version with `ConcurrencyConflict`. All events of a batch are written by one `INSERT`, so all or none are stored. Two writers that passed the check at the same time both try to write the same version; the unique key stops the second, and it gets the same `ConcurrencyConflict`. This holds at MySQL's default isolation level: a reader that sees an older state can only pass the check, never write over a newer event.
- **Stored events cannot be changed:** triggers refuse `UPDATE` and `DELETE` on `events`. With binary logging, MySQL lets a user without the `SUPER` privilege create triggers only with `log_bin_trust_function_creators`, which Docker Compose turns on when MySQL starts and CI sets with `docker/mysql/init.sql`.
- **Serializing:** an `EventSerializer` turns an event into a type name and a JSON payload and back. The type name is stored data, so it does not follow class names; amounts are strings, so they stay exact. `Common` knows no events of a module: the module whose events are stored provides the serializer.
- **Processed commands** are rows of `processed_commands` with the command id as the primary key. A second run of the same id is stopped by the key and gets `CommandAlreadyProcessed`, as with the in-memory store. The time of processing is stored for a retention rule that is not decided yet.
- **Transaction** is a transaction of the default connection; a run inside another run is a savepoint. It does not repeat failed work: repeating a command is the decision of the command bus. Every store uses the same connection, so one transaction covers everything a command writes.
- The database stores run the same contract tests as the in-memory ones, plus tests in which a second connection writes first.

## Rejected alternatives
- **Locking the stream while reading its version (`SELECT … FOR UPDATE`):** it puts writers one after the other, but on a stream that does not exist yet it takes gap locks, and two writers creating lines can deadlock. The unique key gives the same result without locks.
- **A table of streams with a version column, updated on every append:** one more write per append and the same race on that row; the version is already given by the events.
- **Database privileges instead of triggers (the application user without `UPDATE` and `DELETE`):** a stronger barrier, but it needs a second user for migrations. Worth doing in production; triggers protect every environment from the first migration.

## Consequences
- `TRUNCATE` and `DROP` are not stopped by the triggers: removing stored data is an action of an administrator, not of the application.
- `position` is given when a row is inserted, not when its transaction commits, so a transaction that commits later can hold a smaller position. Rebuilding from all events after the fact is not affected; a reader that follows new events while they are written would miss some, and needs another way to find them.
- A deadlock or a lock wait timeout reaches the caller as a database error, not as a conflict, and the command bus does not repeat it. Under load, writers racing on one stream while one of them rolls back can deadlock; treating a deadlock as a conflict needs a check of how Laravel counts nested transactions after MySQL has rolled the whole one back.
- Ids are at most 255 characters and fingerprints 64; longer values fail as database errors. The ids of the core (UUIDs and SHA-256 fingerprints) are far shorter.
- `processed_commands` grows with every command until a retention period is decided.
- A second module that stores events will need a serializer that combines the serializers of the modules.
