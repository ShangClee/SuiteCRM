# 3-Layer Architecture Review
Date: 2026-03-30

## Concept

Replace the current PHP-monolith structure with a clean 3-layer architecture:

```
┌───────────────────────┬─────────────────────────────┐
│  Human GUI            │  AI TUI / Agent              │
│  Browser + SuiteP     │  MCP Server / CLI / LLM      │
├───────────────────────┴─────────────────────────────┤
│  GraphQL Layer (API + business logic in resolvers)  │
│  Auth: JWT / OAuth2                                  │
├─────────────────────────────────────────────────────┤
│  SQLite                                             │
│  Views    = computed/joined data (business logic)   │
│  Triggers = audit trail, cascade deletes            │
│  CTEs     = complex set operations                  │
│  FTS5     = full-text search                        │
└─────────────────────────────────────────────────────┘
```

---

## Layer 1: Database (SQLite)

### Why SQLite?
- Embedded, zero-configuration, file-based
- Modern SQLite (3.35+) supports: CTEs, window functions, JSON, FTS5 full-text search
- Extreme simplicity for deployment and backup (single file)
- Used at scale by WhatsApp, Apple, Notion

### Business Logic IN the DB
Complex logic lives in views and CTEs, not PHP:

```sql
-- Example: view that computes geo status + contact count in one shot
CREATE VIEW account_with_geo_status AS
SELECT
  a.*,
  CASE WHEN a.lat IS NOT NULL THEN 'geocoded' ELSE 'pending' END AS geo_status,
  COUNT(c.id) AS contact_count,
  json_group_array(json_object('id', c.id, 'name', c.first_name)) AS contacts_json
FROM accounts a
LEFT JOIN accounts_contacts_1_c rel
  ON rel.accounts_contacts_1_accounts_id = a.id AND rel.deleted = 0
LEFT JOIN contacts c
  ON c.id = rel.accounts_contacts_1_contacts_id AND c.deleted = 0
WHERE a.deleted = 0
GROUP BY a.id;
```

GraphQL resolver for `accounts` then becomes nearly a pass-through:
```
Query.accounts → SELECT * FROM account_with_geo_status WHERE ...
```

### SQLite Multi-User Patterns Considered

| Model | Description | Fit |
|-------|-------------|-----|
| One SQLite per tenant | Each company = isolated file | Good for SaaS isolation |
| Litestream replication | Single writer + S3-streamed replicas | Good for availability |
| libSQL / Turso | SQLite-compatible + distributed + HTTP | Good for multi-region |
| SQLite as dev/test only | MySQL in prod, SQLite for local dev | Lowest risk starting point |

### SQLite Limitations to Watch
- Single writer at a time (WAL mode mitigates but doesn't eliminate)
- No DB-level user accounts / ACL (ACL must live at GraphQL layer)
- No stored procedures (logic goes in views/CTEs or resolvers instead)
- Not a drop-in replacement for MySQL — schema migration required

---

## Layer 2: GraphQL (API + Business Logic)

### Can GraphQL Replace PHP Business Logic?

Yes — **GraphQL resolvers ARE business logic**, organized differently from PHP services.

```
Current (PHP Fat Service)           GraphQL Resolver Model
──────────────────────────          ──────────────────────
AccountGeocodeService.php           type Mutation {
  syncRelatedBeans()                  geocodeAccount(id: ID!): Account
  updateGeocode()                   }
  called from: thin hook            resolver geocodeAccount(id):
                                      1. fetch from SQLite
                                      2. call maps API
                                      3. bulk update related rows
                                      4. return Account
```

### Where Resolver Code Lives — Options

| Option | Description | Trade-off |
|--------|-------------|-----------|
| PHP (Lighthouse) | Reuse existing services | Low migration cost |
| Node.js (GraphQL Yoga) | Clean break, modern ecosystem | Higher migration cost |
| Go (gqlgen) | Fast, strongly typed | Largest rewrite |
| DB-first (Hasura-style) | Auto-generate from schema | Hasura doesn't support SQLite |
| DB views only | Resolvers are pass-throughs | Only works for read-heavy logic |

### What GraphQL Gives for Free
- **AI introspection**: AI agents can discover schema and build queries without hand-crafted prompts
- **Exact field selection**: No over-fetching (AI/clients request only what they need)
- **Self-documenting types**: Schema IS the contract
- **Single round trip**: Nested data in one query (replaces multiple REST calls)

```graphql
# AI agent single query — no round trips
query {
  accounts(filter: { industry: "Technology" }) {
    name
    geoStatus
    contacts { email }
    openCases { subject priority }
  }
}
```

### Challenge: Dynamic Module System
SuiteCRM modules have runtime-defined fields (added via Studio). Generating a static GraphQL schema over a dynamic field system requires a schema generation step — likely at startup or on module change events.

---

## Layer 3: Frontend

### Human GUI
- Browser-based (SuiteP or replacement UI)
- Queries GraphQL directly
- Standard web stack

### AI TUI / Agent Interface
- **MCP Server** over GraphQL: expose schema as Claude/LLM tools
- **CLI**: shell commands that wrap GraphQL mutations/queries
- **Agent-native**: AI introspects schema, builds queries autonomously

MCP Server approach is the most forward-looking — it lets any LLM (Claude, GPT, etc.) call SuiteCRM operations as native tools without custom prompting.

---

## Relationship to Current Work

The recent improvement phases have been moving logic downward:

```
hooks (scattered)                  [done ✓]
  ↓
fat services (consolidated PHP)    [done ✓]
  ↓
bulk SQL called from services      [done ✓ — BulkOperations.php]
  ↓
SQL views/CTEs in DB               [proposed — 3-layer target]
  +
GraphQL resolvers for mutations    [proposed — replaces REST V8]
  +
AI agent interface (MCP/TUI)       [proposed — new capability]
```

The `expand-n-plus-one-elimination` change (pending) may become unnecessary if relationships are expressed as SQLite views — `bulkGetRelatedIds()` is redundant when the view pre-joins the data.

---

## Open Questions

1. **Scope**: Full replacement of SuiteCRM's MySQL, or new module/service running alongside it?
2. **GraphQL host language**: Stay in PHP (Lighthouse), or break toward Node.js / Go?
3. **SQLite concurrency model**: Which multi-user pattern fits the deployment target?
4. **AI TUI specifics**: MCP Server, raw CLI, or agent-native GraphQL endpoint?
5. **Schema generation**: How to auto-generate GraphQL schema from dynamic SuiteCRM module definitions?

---

## Decision Log

| Topic | Decision | Reason |
|-------|----------|--------|
| YDB | ❌ Not pursuing | Not relevant to project direction |
| SQLite | ✅ Preferred DB target | Simplicity, embedded, modern capabilities |
| Stored procedures | ❌ Rejected | Not supported in SQLite; hard to test |
| Business logic location | Views/CTEs in SQLite + GraphQL resolvers | Keeps logic visible, testable, version-controlled |
| AI interface | GraphQL + MCP Server (to be decided) | AI introspects schema natively |
