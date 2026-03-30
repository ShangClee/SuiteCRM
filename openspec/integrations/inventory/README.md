## Integration Inventory

Date: 20260330

This inventory is the source of truth for all external systems that call SuiteCRM APIs.

Rules:

- One record per external system.
- Do not store secrets in this repository.
- Include discovery evidence for how the integration was found and what endpoints it calls.

## Location and Naming

Inventory records live in:

- `openspec/integrations/inventory/records/`

File naming:

- `<integration-key>.md` where `<integration-key>` is kebab-case (e.g., `acme-erp-sync`, `marketing-automation`, `customer-portal`).

## Status Lifecycle

Each record MUST use one of these statuses:

- `discovered`: Identified but not yet assessed.
- `assessed`: Understood at a high level (endpoints, auth model, owner, criticality).
- `planned`: Migration plan exists (target grant type/endpoints and acceptance criteria drafted).
- `migrating`: In active implementation/testing.
- `migrated`: Cut over to V8 in production.
- `verified`: Meets acceptance criteria in production and has rollback confidence.
- `retired`: Integration decommissioned or no longer calls SuiteCRM.

## Required Fields (Record Template)

Create a record using this template.

### Identity

- Integration Key:
- Owner:
- Escalation Contact:
- Source System:
- Environments:
- Business Criticality:
- Purpose:

### Current API Usage (Legacy)

- API Surface:
- Base URL:
- Endpoints:
- Legacy Methods / Calls:
- Modules Touched:
- Fields Touched:
- Relationships Touched:
- Data Direction:
- Frequency:
- Peak Volume:
- SLA Expectations:

### Authentication and Credentials

- Auth Model:
- Credential Storage Location:
- Rotation Policy:
- Service Account / User Context:

### V8 Migration Target

- OAuth2 Grant Type:
- OAuth2 Client Identity:
- V8 Endpoints:
- V8 Module Names:
- V8 Relationship Link Names:

### Verification and Rollback

- Acceptance Criteria Location:
- Verification Evidence:
- Rollback Plan:

### Discovery Evidence

Attach evidence from at least two independent sources before declaring the inventory complete.

- Evidence Type:
- Evidence Source:
- Evidence Summary:

## Evidence Attachment Guidance

Recommended evidence formats:

- HTTP logs: include the log source, time window, and a sample of request paths that show legacy endpoint usage.
- Code/config repos: include repository name, file path(s), and the matched endpoint strings.
- Stakeholder confirmation: include date, contact, and summary of what was confirmed.

## Spec Alignment

This inventory is designed to satisfy `integration-inventory` requirements:

- Record per external system.
- Mandatory discovery and migration fields.
- Discovery evidence attachment.
- Completeness based on multiple confirmation sources.
- Standard status lifecycle.
