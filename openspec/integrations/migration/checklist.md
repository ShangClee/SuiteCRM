## Per-Integration Migration Checklist

Date: 20260330

Create or update the inventory record first. Then apply this checklist.

## Plan

- Confirm integration owner and escalation contact.
- Confirm business criticality, SLA expectations, and maintenance windows.
- Confirm legacy endpoints and methods in use (SOAP, REST v4, REST v4_1).
- Confirm auth model (service account vs user-context) and credential storage.
- Choose OAuth2 grant type and document rationale.
- Identify V8 endpoints required for all integration workflows.
- Define acceptance criteria for each critical workflow.
- Define rollback plan and rollback trigger conditions.

## Migrate

- Implement token acquisition for the selected OAuth2 grant type.
- Replace legacy calls with V8 endpoint calls.
- Validate field and relationship naming via V8 metadata endpoints.
- Align error handling with V8 response patterns.
- Ensure credentials are stored outside source control and are rotatable.

## Verify

- Execute acceptance criteria for each workflow in a non-production environment.
- Confirm permissions and least privilege under the token identity.
- Record verification evidence (logs, outputs, screenshots where relevant).
- Record any parity gaps and the remediation decision.

## Cutover

- Define cutover steps, timing, and monitoring signals.
- Perform cutover in production.
- Monitor for functional failures, permission issues, and data integrity issues.
- Confirm integration status transition to `migrated`.

## Post-Cutover

- Re-run acceptance criteria in production.
- Confirm rollback remains possible while legacy endpoints remain enabled.
- Mark integration `verified` only when gates are satisfied.

## Rollback (If Needed)

- Execute the rollback procedure.
- Capture root cause and remediation plan before attempting a second cutover.
