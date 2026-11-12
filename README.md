# Earning Line

A payroll earning line with a transparent history of manual adjustments.

The system calculates a line automatically. A payroll specialist can correct it with signed manual adjustments, each with a mandatory comment. Adjustments are append-only, and the first one freezes the line against further automatic recalculation. The current value and the full history are available at any time.

> **Status:** work in progress. See the [plan](docs/project/plan.md) and the [changelog](CHANGELOG.md).

## Running

Everything runs in Docker, so only Docker and `make` are needed.

```bash
make install   # build the image and install dependencies
make qa        # coding style, static analysis with architecture rules, tests
make help      # all commands
```

## Documentation

- [Business rules, lifecycle, edge cases and open questions](docs/payroll/domain.md)
- [Architecture](docs/architecture.md)
- [Payroll architecture](docs/payroll/architecture.md)
- [All documents](docs/README.md)
