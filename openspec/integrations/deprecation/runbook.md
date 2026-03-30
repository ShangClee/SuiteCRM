## Deprecation Runbook (Detect → Warn → Enforce → Disable)

Date: 20260330

This runbook assumes deprecation controls are implemented operationally (reverse proxy/WAF).

## Phase 1: Detect

Goal: measure legacy usage and attribute callers.

- Ensure access logs capture request paths and upstream identity.
- Run legacy usage detection and link results to inventory records.

Rollback:

- Stop any new alerting or dashboards if they are noisy; do not change request routing.

## Phase 2: Warn

Goal: notify callers while keeping behavior compatible.

Options:

- Add an operational notification process to contact integration owners using the inventory.
- Optionally inject a response header for legacy endpoints at the reverse proxy layer (example: `Deprecation: true`), without changing status codes.

Rollback:

- Remove the injected header and communication artifacts if they cause issues.

## Phase 3: Enforce

Goal: restrict legacy endpoint access while allowing explicit exceptions.

Strategy:

- Maintain an allowlist for callers that are actively migrating.
- For non-allowlisted callers, return an explicit failure response at the proxy/WAF layer.

Rollback:

- Revert enforcement rules to allow all traffic.

## Phase 4: Disable

Goal: fully disable legacy endpoints after verified zero usage and stakeholder sign-off.

Strategy:

- Return a consistent failure response for all legacy endpoints (example: `410 Gone`).

Rollback:

- Temporarily re-enable endpoints by reverting proxy/WAF rules while investigating unexpected consumers.

## Validation

Before progressing phases, confirm:

- Inventory completeness and integration owner sign-off.
- “Zero legacy usage” evidence over the agreed validation window.
