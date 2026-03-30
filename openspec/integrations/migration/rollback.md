## Rollback Procedure (While Legacy Endpoints Remain Enabled)

Date: 20260330

Goal: restore integration service if the V8 migration fails or causes unacceptable risk.

## Preconditions

- Legacy endpoints remain enabled and reachable.
- The integration can be switched between legacy and V8 modes without redeploying secrets into source control.

## Rollback Triggers

- Data integrity risk detected.
- Critical workflow failure beyond agreed tolerance.
- Authentication or permission failures that block core operation.

## Procedure

1) Switch integration traffic back to the legacy API path.

2) Confirm critical workflows are restored.

3) Stop any scheduled jobs that may exacerbate bad state until stability is confirmed.

4) Capture evidence:

- Failure symptoms and timestamps
- Example requests/responses
- Any parity gaps discovered

5) Open a remediation plan before re-attempting migration.

## Post-Rollback Actions

- Update the inventory record status back to `planned` or `assessed`.
- Record the root cause and remediation decision.
