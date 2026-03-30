## 1. Baseline and Hotspot Inventory

- [x] 1.1 Identify top N+1 hotspots from production-like request paths
- [x] 1.2 Select 2–3 initial “must win” flows for baselining and regression guards
- [x] 1.3 Capture baseline query counts and response timings for selected flows
- [x] 1.4 Verify baselines are reproducible on a seeded dataset

## 2. Bulk Data Primitives

- [x] 2.1 Implement bulk read-by-ids helper with chunking and field projection
- [x] 2.2 Implement bulk field update-by-ids helper with safe quoting and chunking
- [x] 2.3 Implement bulk relationship add helper with join-table metadata resolution
- [x] 2.4 Implement bulk relationship remove helper using soft delete semantics
- [x] 2.5 Add behavior-preserving mode entry points for hook/ACL-sensitive operations
- [x] 2.6 Add unit tests for bulk primitives with representative relationships

## 3. Refactor Initial Hotspots

- [x] 3.1 Refactor one controller path that re-retrieves the same parent bean in a loop
- [x] 3.2 Refactor one relationship write loop to bulk add/remove related ids
- [x] 3.3 Refactor one read loop that hydrates beans per id to a bulk row query
- [x] 3.4 Confirm functional equivalence for the refactored flows

## 4. Custom Fields Index Management

- [x] 4.1 Identify custom-field filters/joins used by the selected flows
- [x] 4.2 Propose composite indexes on affected `_cstm` tables aligned to query patterns
- [x] 4.3 Create migration SQL for applying indexes and rollback SQL for removing them
- [x] 4.4 Validate index effectiveness with EXPLAIN before and after applying indexes

## 5. N+1 Regression Guard

- [x] 5.1 Implement query count ceiling checks for the selected flows in automated verification
- [x] 5.2 Implement detection of repeated per-record retrieval patterns for targeted flows
- [x] 5.3 Ensure findings include safe call-site identifiers and no sensitive data
- [x] 5.4 Add CI-friendly verification for regression guard checks

## 6. Verification and Rollout

- [x] 6.1 Run PHP lint on changed files (`php -l`) and fix any syntax issues
- [x] 6.2 Run PHPUnit suite for affected areas and resolve failures
- [x] 6.3 Re-measure query counts and timings for selected flows and compare to baseline
- [x] 6.4 Document operator steps for applying index migrations and rolling back
