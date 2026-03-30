# Performance Tooling

## N+1 Scan (static)

Scans the codebase for common N+1 patterns such as:

- `BeanFactory::getBean()` inside loops
- `->get_linked_beans()` inside loops
- `->getBeans()` inside loops

Run:

```bash
php tools/performance/nplusone_scan.php
```

Optional:

```bash
php tools/performance/nplusone_scan.php --path modules
php tools/performance/nplusone_scan.php --path Api
php tools/performance/nplusone_scan.php --limit 200
```

## Runtime Query Count

SuiteCRM already tracks query count via `DBManager::getQueryCount()` / `DBManager::resetQueryCount()`.

For interactive profiling in the UI:

- Enable `show_page_resources` in config to see query count in the footer.
- Enable `dump_slow_queries` and set `slow_query_time_msec` to capture slow queries in logs.

For automated verification, prefer PHPUnit tests that assert a query count ceiling around a targeted flow.
