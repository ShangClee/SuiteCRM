# Targeted Flows (Initial)

These are the initial “must win” flows for N+1 remediation and regression guarding.

## Flow A: FP_events relationship adds

File: `modules/FP_events/controller.php`

Patterns:

- Re-retrieving the same event bean inside loops
- Per-id relationship `get()`/`add()` operations

Measure:

- Exercise the controller action that adds targets/contacts/leads to an event.
- Record `DBManager::getQueryCount()` delta and total elapsed time before/after.

## Flow B: Relationship hydration via Link2::getBeans

File: `data/Link2.php`

Patterns:

- Bean hydration per related id (`BeanFactory::getBean()` inside a loop)

Measure:

- Identify a list view / API response path that calls `getBeans()` for a relationship containing many related rows.
- Record query count and elapsed time before/after adopting bulk row fetch patterns.

## Flow C: API relationship list endpoints (legacy/v4_1)

File: `service/v4_1/SugarWebServiceImplv4_1.php`

Patterns:

- Relationship listing/formatting can fan out into per-row operations in custom implementations.

Measure:

- Call the relationship listing endpoint for a module with large relationship sets.
- Record query count and elapsed time before/after.

## Baseline Recording

Use:

- PHPUnit-style automated checks for query count ceilings (preferred for regression)
- UI footer query count with `show_page_resources` enabled (quick manual check)
- Slow query logging with `dump_slow_queries` / `slow_query_time_msec` for query plan hotspots
