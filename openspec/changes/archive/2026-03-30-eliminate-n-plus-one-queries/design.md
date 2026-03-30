## Context

SuiteCRM’s bean and relationship APIs make it easy to accidentally perform N+1 query patterns: iterating over a result set and calling `BeanFactory::getBean()`/`retrieve()` or loading relationships per record. This is amplified by relationship helpers that ultimately load each related bean individually (e.g., `Link2::getBeans()` calling `BeanFactory::getBean()` per related id in `data/Link2.php`).

Representative high-risk patterns in the codebase include:

- Re-retrieving the same parent bean inside a loop and then calling relationship `add()`/`get()` per iteration (e.g., `modules/FP_events/controller.php`).
- Using relationship helpers that hydrate full beans for each id (e.g., `Link2::getBeans()` in `data/Link2.php`).
- Fetching a list of beans, then for each bean calling `get_linked_beans()` or `load_relationship()` + `getBeans()` and iterating related results (common in modules and legacy APIs).

Constraints:

- Many code paths depend on beans for ACL checks, formatting, and logic hooks. Bulk SQL must be limited to cases where bypassing hooks is safe, or where operations run in privileged contexts (e.g., schedulers) and invariants are preserved.
- SuiteCRM’s relationships are metadata-driven (join tables, role columns, deleted flags). Bulk relationship writes must respect the same schema semantics as Link/Relationship operations.
- Changes must be incremental and verifiable; the goal is to remove hotspots first and establish repeatable patterns for future changes.

## Goals / Non-Goals

**Goals:**

- Provide a standard set of bulk data access and bulk write patterns to replace loop-driven bean retrieval and per-record relationship writes.
- Reduce query counts in targeted high-traffic flows by converting N+1 patterns to set-based queries (`WHERE id IN (...)`, join-table inserts/updates).
- Establish a regression guard for targeted flows so N+1 patterns do not quietly reappear.
- Improve query plans for custom-field filters and joins by adding targeted indexes on `_cstm` tables.

**Non-Goals:**

- Replace the bean layer globally or rewrite core ORM behavior.
- Convert every legacy module in a single pass; prioritization is based on impact/hotspots.
- Change end-user behavior or API semantics beyond performance improvements.

## Decisions

### 1) Use “bulk rows first”, beans only when required

**Decision:** Prefer fetching the minimum required fields for many records as rows via `SugarQuery` (or direct `DBManager` SQL) and only hydrate full beans when necessary for behavior (ACL, hooks, computed fields).

**Rationale:** Beans are expensive: `retrieve()` triggers queries, field processing, and often cascades into related calls. Many flows only need ids + a few columns to make decisions or build join-table writes.

**Alternatives considered:**

- “Rely on BeanFactory cache”: insufficient (small in-memory cache, request-local, doesn’t fix relationship hydration).
- “Rewrite Link2 to avoid bean loading”: too invasive; instead introduce focused bulk query helpers and refactor call sites.

### 2) Introduce reusable bulk primitives instead of ad-hoc SQL per module

**Decision:** Provide a small internal API (service/helper) that encapsulates:

- Bulk selection by id list (chunking, ordering, minimal field projection)
- Bulk relationship add/remove (join-table inserts/updates with deleted flag handling)
- Bulk field updates (single statement updates with `WHERE id IN (...)`)

**Rationale:** Without shared primitives, each module will reinvent SQL, increasing security and correctness risk. A shared layer centralizes safe chunking, quoting, and relationship semantics.

**Alternatives considered:**

- “Refactor each hotspot with custom SQL”: faster initially but hard to maintain and easy to get wrong across relationship types.
- “Use only SugarQuery everywhere”: sometimes insufficient for bulk join-table writes where set-based inserts are needed; allow `DBManager` SQL where required.

### 3) Bulk relationship writes must follow relationship metadata semantics

**Decision:** Bulk relationship operations will:

- Resolve relationship metadata (join table name, lhs/rhs keys, optional role column/value).
- Insert missing links as new join-table rows, or update existing rows to `deleted=0`.
- Remove links by setting `deleted=1` (matching SuiteCRM join-table semantics) rather than hard delete, unless a specific join-table is known to be hard-delete safe.

**Rationale:** Relationship tables in SuiteCRM typically implement soft delete. Correctness depends on respecting role columns and deleted flags.

**Alternatives considered:**

- “Hard delete for remove”: can break audit/history expectations and some modules’ assumptions.
- “Always call `$link->add()` in a loop”: preserves hooks but is the N+1 problem; keep for hook-required cases only.

### 4) Preserve hooks/ACL when business invariants demand it; otherwise use privileged bulk path

**Decision:** Define two operating modes for bulk operations:

- **Behavior-preserving mode:** uses beans/relationships when hooks and ACL must apply (optimized by avoiding re-retrieve of the same parent bean and by batching where possible).
- **Privileged bulk mode:** uses set-based SQL for schedulers/maintenance operations where hooks are not required and operations run under trusted contexts.

**Rationale:** Some workflows rely on hooks (notifications, audit, calculated fields). Others are pure join-table maintenance where SQL is safe and dramatically faster.

### 5) Add targeted composite indexes on `_cstm` tables based on real query patterns

**Decision:** For custom-field-heavy modules, add composite indexes that match common filters/join patterns, typically anchored on `id_c` plus the custom field(s) used in WHERE clauses.

**Rationale:** `_cstm` joins are frequent; missing composite indexes can force table scans or poor join plans. Indexes must be driven by query patterns to avoid excessive index bloat.

**Alternatives considered:**

- “Index every custom field”: too costly in storage and write amplification.
- “Rely on single-column indexes”: insufficient when the optimizer needs a combined access path for join + filter.

## Risks / Trade-offs

- Bulk SQL bypasses logic hooks → Limit privileged bulk mode to safe operations; document exceptions; use behavior-preserving mode when hooks are required.
- ACL/security bypass in bulk operations → Ensure bulk helpers either accept an explicit “trusted context” or enforce ACL checks at call sites where user context matters.
- Large `IN (...)` lists can degrade performance → Implement chunking (e.g., 200–1000 ids per chunk depending on DB) and stream results where possible.
- Index changes can lock tables / increase disk usage → Provide migration guidance, keep indexes targeted, validate with EXPLAIN, and support rollback (drop index).
- Divergence between row-based and bean-based behavior → Add equivalence checks for targeted flows (same record counts/relationships before/after).

## Migration Plan

1. Inventory and rank hotspots by estimated impact (query count * call frequency): prioritize controllers/services and known heavy modules.
2. Add bulk primitives (bulk select, bulk update, bulk relationship ops) and adopt them in one or two representative hotspots to validate approach.
3. Add regression guards for the selected hotspots (query count baselines and functional equivalence assertions).
4. Expand refactors iteratively module-by-module, keeping diffs small and verifiable.
5. Add targeted indexes for the queries observed in the refactored flows; provide an operator migration script for index application and rollback.

Rollback strategy:

- Refactor rollbacks are code-level reverts for affected call sites.
- Index rollbacks are `DROP INDEX` for newly introduced indexes, documented per table.

## Open Questions

- Which flows are the initial “must win” targets (list views, API endpoints, schedulers) to establish baselines and regression guards?
- For relationship bulk operations, which modules rely on relationship-add hooks and therefore require behavior-preserving mode?
- What is the preferred mechanism for regression guarding query counts in CI (DB query counter instrumentation vs. targeted integration tests)?
