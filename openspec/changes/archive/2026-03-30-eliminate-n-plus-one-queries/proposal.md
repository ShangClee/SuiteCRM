## Why

SuiteCRM frequently performs repeated per-record fetches and relationship loads inside loops (N+1 queries), which causes slow list views, heavy API responses, and excessive database load at scale. This change establishes a systematic path to convert loop-driven bean retrieval and relationship writes into bulk `SugarQuery`/SQL operations with measurable performance gains.

## What Changes

- Introduce a small set of reusable bulk data access/write helpers (bulk select by IDs, bulk relationship add/remove, bulk field updates) built on `SugarQuery` and `DBManager`.
- Refactor high-impact N+1 call sites (especially controller/service paths) to use bulk operations instead of `BeanFactory::getBean()` and per-record relationship `add()` inside loops.
- Add targeted database indexes (including composite indexes on `_cstm` tables where custom fields are used for filtering/joining) to reduce full scans and improve join performance.
- Add guardrails for correctness (ACL-aware paths where required, safe chunking, id validation) and verification (query count/perf checks for selected flows).

## Capabilities

### New Capabilities

- `bulk-data-operations`: Standard bulk read/write patterns for SuiteCRM data operations that avoid per-record bean retrieval in loops.
- `custom-fields-index-management`: Guidance and repeatable approach to define and apply indexes that optimize joins/filters involving `_cstm` tables.
- `n-plus-one-regression-guard`: Lightweight regression checks (query count baselines for targeted flows) to prevent reintroducing N+1 patterns.

### Modified Capabilities

- (none)

## Impact

- Affected areas: modules with mass relationship updates, legacy controllers, API services, and any code paths that call `BeanFactory::getBean()`/`newBean()` or `Link2::getBeans()` inside loops.
- Database: new/updated indexes on selected tables (especially `_cstm` tables) may require admin-run migrations on existing instances.
- Performance: fewer queries per request, reduced DB latency, improved throughput under concurrency.
- Compatibility: behavior should remain functionally equivalent; changes focus on implementation and performance, not feature semantics.

## Goals

- Reduce query counts and response times for selected high-traffic flows by replacing N+1 patterns with bulk operations.
- Establish repeatable, well-scoped patterns so future code avoids reintroducing N+1 loops.
- Improve database query plans for common custom-field filters and joins via indexes.

## Non-goals

- Re-architect the entire ORM/bean layer or remove beans from the codebase.
- Rewrite all legacy modules in a single pass; this change prioritizes hotspots first.
- Change UI/feature behavior beyond performance and internal data access patterns.

## Risks

- Data integrity risk if bulk relationship writes bypass logic hooks or required invariants; mitigated by constraining direct SQL to safe cases and documenting when bean-based saves are required.
- ACL/security risk if bulk reads/writes ignore permission checks; mitigated by defining when operations must be ACL-aware and where system-level operations are acceptable (e.g., scheduler jobs).
- Operational risk from index changes (lock time, disk usage); mitigated by targeted indexing, rollout guidance, and measuring query plans.

## Verification

- Add automated checks for selected flows to assert query count ceilings and validate result equivalence.
- Run existing PHPUnit suites for impacted modules/services.
- Validate SQL query plans (EXPLAIN) for targeted queries before/after index changes.
- Run representative performance smoke tests (list view/API endpoints) on datasets that previously exhibited N+1 behavior.

## Installation / Migration Impact

- Index additions/changes require applying SQL migrations on existing deployments. Provide an operator-facing migration script and guidance for running during maintenance windows where needed.
