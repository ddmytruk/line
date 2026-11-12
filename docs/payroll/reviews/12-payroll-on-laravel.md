# Review of step 12: Payroll on Laravel

Scope: stored Payroll events, the line history in MySQL, the wiring of the module, the artisan commands, the HTTP API, the system clock and ADR 0014. The reviewer probed the API and the commands with damaged stored data, empty and wrongly typed fields, form bodies and huge amounts, and ran mutations of the new code: clearing the histories with `TRUNCATE` and leaving the projector out of the bus were caught.

| # | Finding | Decision | Change |
|---|---|---|---|
| 1 | A stored value the domain refuses (an amount with three decimals, a malformed line id) was answered as a refusal of the request: HTTP 422 or exit code 1, so a client would keep correcting a correct request | accepted | damaged stored events and histories are reported as damaged data; HTTP answers 500; tested through the API |
| 2 | An empty `commandId` was accepted over HTTP, so clients that leave it empty would share one id | accepted | refused with 422 |
| 3 | Error pages for faults and unknown routes were HTML | accepted | every error is answered as JSON |
| 4 | The API showed times with milliseconds while the history keeps microseconds | accepted | microseconds |
| 5 | Mutations survived: the time zone of the clock, the check of the specialist option, a missing line on the command line, an empty command id option, a body that is not JSON | accepted | a test for each |
| 6 | A negative `expectedVersion` was answered as a conflict | accepted | refused with 422 |
| 7 | A rebuild running while commands are sent can be overwritten with a history read before it | accepted | ADR 0014 says the rebuild runs while no commands are sent |
| 8 | The documents named one exit code and left out where the clock comes from | accepted | ADR 0014 and the architecture name both exit codes and the provider of the clock |
| 9 | Found after the review, when the API was served for the first time: the application had no entry point for a web server, so the API was reachable only from tests | accepted | `app/public/index.php`; the API was checked over a real HTTP connection |
