## Access Log Analysis Procedure

Date: 20260330

Goal: identify every caller hitting legacy endpoints and produce evidence suitable for the integration inventory.

## Inputs

- HTTP access logs from all web nodes that serve SuiteCRM
- Time window to analyze (recommended: at least one full business cycle)

The log format MUST include:

- Timestamp
- Remote address (or upstream address)
- HTTP method and request path
- Status code
- User agent (recommended)

## Target Endpoints

Match any request path containing:

- `/soap.php`
- `/service/v2/soap.php` … `/service/v4_1/soap.php` (and similar versioned paths)
- `/service/v4/rest.php`
- `/service/v4_1/rest.php`

## Procedure

1) Collect logs for the time window and ensure they include all nodes.

2) Run the extraction tool:

- `php build/api-audit/legacy_api_usage.php --log <path> --format json`

You may repeat `--log` multiple times.

3) Review the output:

- Identify unique callers (by IP and user-agent).
- Identify which legacy endpoints are used.
- Identify volume patterns (spikes, schedule, steady-state).

4) Create or update inventory records:

- Create a record if no record exists for the caller/system.
- Attach log evidence by capturing:
  - Log source (cluster/node, file names)
  - Time window analyzed
  - Sample request paths and counts
  - Any attribution hints (reverse proxy identity, user-agent, upstream headers)

## Outputs

The analysis output SHOULD provide:

- Aggregated counts by caller and endpoint
- First seen / last seen timestamps within the window
- A short list of sample request paths (optional)

## Notes

- If a reverse proxy is in place, IP addresses may reflect the proxy rather than the true caller. Prefer logs that preserve upstream identity.
- If `Authorization` headers are later required for V8 calls, validate that proxy and web server configuration preserves headers for `/Api/*` routes.
