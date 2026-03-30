## Gates

Date: 20260330

This document defines gates for marking an integration “verified” and for approving legacy endpoint retirement.

## Gate: Mark Integration Verified

An integration MAY be marked `verified` only when:

- Acceptance criteria exist for all critical workflows.
- Acceptance criteria have been executed successfully in production.
- Verification evidence is recorded and linked from the inventory record.
- Any parity gaps that affect required workflows are resolved or explicitly accepted.
- A rollback plan is documented and has been validated as feasible while legacy endpoints remain enabled.

## Gate: Approve Legacy Endpoint Retirement

Legacy endpoint retirement MAY be approved only when:

- Inventory is complete and signed off by stakeholders for the deployment scope.
- All integrations are marked `verified` or `retired`.
- “Zero legacy usage” is demonstrated for the full validation period using access-log evidence.
- A rollback strategy exists for re-enabling access if unexpected consumers appear.
