# Ticket Tailor payments dashboard

An organiser-facing dashboard built with PHP 8.4, Laminas Mezzio, React, TypeScript, Vite, Tailwind CSS v4 with custom theme tokens, and Zod. It reads the supplied historical JSON extract and reports earnings, payout history, and funds that remain available or become available later.

## Setup and run

The production-like stack requires Docker with the Compose v2 plugin; Docker Desktop provides both on macOS and Windows. From the project root, run:

```sh
docker compose up --build
```

The same command is available as `make start` or `./scripts/start`. Open <http://127.0.0.1:8081> and stop the stack with `docker compose down`.

For frontend development with Vite hot reload, start the optional Compose profile:

```sh
docker compose --profile dev up --build
```

Open <http://127.0.0.1:5173>. API requests continue to use the PHP service in Compose.

Configuration is read from the environment. `.env.example` documents `RECORDS_PATH` and `LOG_LEVEL`; Compose uses `/app/data/records.json` and `warning` respectively.

## Tests and checks

The main check runs in Docker, installs locked dependencies, validates `composer.json`, and runs PHPUnit, PHPStan level 8, PHPCS, Vitest, ESLint, Prettier, TypeScript, the Vite production build, and `docker compose config`:

```sh
./scripts/check
```

`make check` is an alias. The script cleans generated test and build artifacts before and after the run. `make test` is a smaller local command for PHPUnit and Vitest and expects local Composer and npm dependencies to be installed.

Playwright is deliberately separate from the main check and expects the production stack on port 8081. It exercises desktop and mobile Chromium, including keyboard interaction and an axe accessibility smoke check:

```sh
docker compose up --build -d
cd frontend
npm ci
npx playwright install chromium
npm run e2e
```

Generated output can be removed with `make clean`; `./scripts/clean --all` also removes `backend/vendor` and `frontend/node_modules`.

## Design and architecture decisions

The backend uses PSR-4 namespaces and four focused layers:

- **Domain** parses and validates records, applies refund and payout rules, reconciles balances, and returns the serializable report.
- **Application** contains the single `GetDashboardReport` use case, which combines a validated dashboard query with a `RecordSource`.
- **Infrastructure** reads the bounded JSON fixture, environment settings, and logging configuration.
- **HTTP** validates query parameters, maps the use case to PSR-7 JSON responses, and provides safe not-found, method, and error handling. Mezzio wiring lives under `backend/config/`.

PHP is the sole authority for financial calculations. All money uses integer minor GBP units, with no floating-point arithmetic. Exact duplicate deliveries are made idempotent, while refunds, payout membership, availability, and lifetime balances are reconciled before a report is returned.

React formats and presents values returned by PHP; it does not recalculate money. Zod validates the response at runtime, and the report hook aborts obsolete requests and prevents stale responses from replacing newer results. The fixture contract is shared through `docs/fixtures/dashboard.json`.

Requests flow from nginx through Mezzio's error handling, routing, and method guard to `DashboardHandler`, then through `DashboardQuery`, `GetDashboardReport`, `JsonSource`, and the domain ledger. The resulting report is serialized as JSON, validated by Zod, and rendered by React. The API contract is documented in [docs/openapi.yaml](docs/openapi.yaml).

## Security considerations

- The JSON source has byte, nesting, and record-count limits. `RECORDS_PATH` comes only from environment configuration and cannot be selected through request input.
- The ledger is validated fail-closed. Public errors contain a generic message and request reference rather than source records, validation codes, stack traces, or internal paths.
- Logs contain a safe diagnostic code and request reference for investigation.
- nginx accepts GET only, limits request bodies and request rates, disables version tokens, applies timeouts, and sends CSP, framing, MIME-sniffing, referrer, permissions, cross-origin, and no-store headers.
- The production PHP and nginx services use pinned images, read-only filesystems, resource and process limits, `no-new-privileges`, and dropped Linux capabilities. PHP-FPM runs as an unprivileged user.
- Authentication, organiser-level multi-tenant authorisation, TLS termination, external secret management, and live Stripe webhook verification are deployment concerns outside this local exercise.

## Accounting assumptions and data edge cases

### Post-payout refunds

Payout history is immutable. If a refund is created after its charge has already been included in a payout, the historical payout remains unchanged and the refund becomes a separate negative balance movement. It reduces the remaining or available balance according to its `available_on` date instead of disappearing from the ledger or rewriting what reached the bank.

The fixture demonstrates this with charge `ch_0007`: its £45.00 charge was included in payout `po_0001`, sent on 16 June 2026. Refund `re_0004` was then created on 20 June for £45.00. Its £1.20 Ticket Tailor fee credit produces a separate net movement of `-4380` minor units (-£43.80), available on 20 June.

### Duplicate deliveries

Records use immutable IDs. Deliveries with the same ID and identical canonical payload are processed once, making ingestion idempotent and preventing retries from changing totals. The 67 fixture rows contain identical deliveries of `re_0002` and `ch_0005`, leaving 65 unique records. A repeated ID with a conflicting payload fails closed.

### Partial refunds and fees

Ticket Tailor’s fee is returned proportionally on refunds, while Stripe’s original processing fee is retained. Partial refund credits use cumulative integer half-up rounding: each refund receives the difference between the newly accumulated entitlement and the credit already issued. This keeps all calculations in integer minor GBP units and prevents rounding drift across several partial refunds.

### Availability and payout dates

`available_on` identifies when a movement becomes eligible for payout. It is not a predicted bank-arrival date. The extract contains no future payout schedule, so the application reports available and later balances separately from actual payout history and never invents a future payout date.

Reporting windows use inclusive UTC calendar days. The default reporting date is the fixture’s historical date, 20 June 2026. Changing it changes the window over the supplied records; it does not turn the fixture into a forecast or connect the application to Stripe.

### Invalid or contradictory data

The backend validates the complete ledger before building a report. It fails closed for malformed records, unsupported currency, invalid amounts or dates, inconsistent fee arithmetic, invalid net values, unknown refund references, refunds before their charge, refunds exceeding their charge, incorrect proportional fee credits, conflicting duplicate IDs, duplicate payout allocation, unknown payout members, unavailable movements included in a payout, payout totals that do not reconcile, and failed balance reconciliation.

No partial financial report is returned in these cases. The API returns a safe generic error and request reference without exposing source records, stack traces, internal paths, or validation details. Server logs contain a safe diagnostic code and the request reference. In production these events could feed operational data-quality monitoring so an extract could be corrected and reprocessed; no alerting or admin system is included in this exercise.

## Independent financial verification

Expected values were calculated separately from the production calculation path before the production calculator was written. The supplied records were keyed by immutable ID, exact duplicate deliveries were accounted for, each financial category was summed independently, payout membership was separately reconciled, and unpaid movements were grouped by availability date. The full method and fixture hash are recorded in [docs/FIXTURE_PROFILE.md](docs/FIXTURE_PROFILE.md).

For the inclusive 30-day period ending 20 June 2026, the independent oracles are:

| Value | GBP | Minor units |
| --- | ---: | ---: |
| Gross sales | £1,162.50 | 116250 |
| Refunds | £107.50 | 10750 |
| Ticket Tailor fee credits | £3.20 | 320 |
| Ticket Tailor fees after credits | £38.05 | 3805 |
| Stripe fees retained | £28.31 | 2831 |
| Lifetime net | £988.64 | 98864 |
| Paid out or sent | £445.36 | 44536 |
| Remaining or unpaid | £543.28 | 54328 |

The remaining movements were independently grouped as £79.62 on 16 June, £507.46 on 17 June, and -£43.80 on 20 June. Payout `po_0001` contains 26 movements whose nets sum to £445.36. The central reconciliation equation is:

```text
paid out or sent + remaining or unpaid = lifetime net
£445.36 + £543.28 = £988.64
```

Partial refund fee credits and the rule that Stripe fees are not returned were also checked independently. Test expectations are fixed oracle values; tests do not obtain their expected totals by calling the production ledger.

## Trade-offs and scope decisions

Given the exercise scope, the implementation prioritised correct financial behaviour, deterministic reconciliation, API correctness, a usable organiser-facing dashboard, automated validation, and desktop/mobile browser coverage.

The fixed JSON source keeps review and reconciliation deterministic. A database, pagination, authentication, live Stripe integration, telemetry, and operational alerting were deferred because they do not improve the supplied fixture’s financial answer. Availability remains an eligibility view rather than a payout forecast because the extract has no future payout schedule.

## Use of AI

AI was used selectively for project scaffolding and file organisation, Mezzio and frontend setup, exploring implementation options, producing initial code suggestions, reviewing PHP, TypeScript, and React code for consistency, identifying edge cases, improving accessibility and presentation, drafting documentation, and suggesting tests and validation cases.

The work was directed with explicit financial and architectural constraints: PHP had to remain the financial authority; money had to use integer minor units; availability could not be presented as a predicted payout date; contradictory ledgers had to fail closed; and expected values had to remain independent oracles rather than outputs copied from the production calculator.

Suggestions were reviewed rather than accepted blindly. They were retained when they improved clarity, maintainability, accessibility, or defensive validation. Suggestions were rejected or revised when they would move financial calculation into React, infer future payout dates, hide invalid records, return partial results for contradictory data, weaken payout reconciliation, derive test expectations from production code, add unsupported infrastructure, or otherwise expand the exercise beyond its requirements.

The final fee rules, treatment of post-payout refunds, payout membership, reconciliation equation, expected figures, validation behaviour, and architectural decisions were checked manually against the supplied records and independent fixture profile.
