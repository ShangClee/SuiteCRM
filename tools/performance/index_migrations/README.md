# Index Migrations (Manual)

SuiteCRM does not ship a built-in migration runner for schema changes in this repository. Index changes are applied manually by operators.

## Workflow

1. Identify a slow query or frequently executed query that joins a base module table to its `_cstm` table and filters on one or more custom fields.
2. Extract the `_cstm` table name and the custom field(s) used in the WHERE clause.
3. Generate candidate SQL for a composite index starting with `id_c`.

Generate:

```bash
php tools/performance/generate_cstm_index_sql.php --table accounts_cstm --fields industry_c
```

Apply:

- Use the `create` statement during a maintenance window if needed.

Rollback:

- Use the `drop` statement if the index causes issues (disk usage, write amplification, locking).

## Validation

- Run `EXPLAIN` on the query before and after applying the index.
- Confirm the optimizer uses the new index on the `_cstm` table join/filter.
