## “Zero Legacy Usage” Verification Procedure

Date: 20260330

Goal: provide evidence that legacy endpoints have no remaining consumers for an agreed validation period.

## Preconditions

- Inventory is complete and all integrations are marked `verified` or `retired`.
- Logging coverage includes all web nodes serving SuiteCRM.

## Validation Period

Define a validation period appropriate for the deployment:

- Minimum: one full business cycle
- Prefer: multiple cycles if integrations include monthly/quarterly jobs

## Procedure

1) Run access-log detection across the full validation period using `build/api-audit/legacy_api_usage.php`.

2) Confirm that there are zero hits to:

- `/soap.php` and versioned `service/*/soap.php`
- `/service/v4/rest.php` and `/service/v4_1/rest.php`

3) If any hits exist:

- Identify the caller (IP/user-agent/identity).
- Link evidence to the matching inventory record.
- Do not proceed with retirement until the integration is migrated and verified, or explicitly retired.

4) If zero hits exist:

- Record the validation window and evidence location (log sources and tool output).
- Proceed to retirement approval gates.

## Output to Record

Record the following in the retirement approval record/runbook:

- Validation window (start/end)
- Log sources analyzed
- Tool version and invocation parameters
- Statement of zero hits for all legacy endpoints
