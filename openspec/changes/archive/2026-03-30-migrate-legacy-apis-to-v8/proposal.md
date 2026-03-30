## Why

Date: 20260330

SuiteCRM exposes legacy SOAP (`soap.php`) and REST v4/v4_1 (`service/v4*/rest.php`) APIs that authenticate using PHP sessions. Modern integrations and frontends benefit from the V8 API, which is OAuth2-protected and returns standard JSON payloads. Migrating external systems to V8 reduces credential/session handling risk, improves interoperability, and simplifies long-term API maintenance.

## Goals

- Identify every external integration that calls SOAP or REST v4/v4_1.
- Provide a safe migration path for each integration to V8 OAuth2 + V8 endpoints.
- Reduce operational risk by standardizing authentication on OAuth2 bearer tokens.
- Establish repeatable verification to prove parity between legacy and V8 behaviors used by integrations.

## Non-goals

- Redesign SuiteCRM data model or module logic.
- Change internal (in-app) UI behavior unrelated to API usage.
- Build new business features for external systems beyond adapting their API calls.

## What Changes

- Create an integration inventory (owners, purpose, endpoints used, auth model, data flows, SLAs).
- Add detection guidance to find legacy API usage from logs and code/config repositories.
- Define V8 endpoint/auth mappings for common legacy v4 methods and SOAP use cases.
- Provide a phased migration plan (pilot, parallel run, cutover, rollback) for all integrations.
- Optionally introduce deprecation controls and observability to support a controlled retirement of legacy endpoints. **BREAKING** only if legacy endpoints are disabled in a deployment.

## Capabilities

### New Capabilities

- `integration-inventory`: A standardized inventory format and process to identify all external SuiteCRM integrations and the APIs they use.
- `legacy-api-usage-detection`: Guidance and tooling hooks to detect SOAP and REST v4/v4_1 usage from HTTP logs and code/config searches.
- `v8-auth-for-integrations`: Standard OAuth2 client patterns for integrations (client-credentials, auth-code, password where unavoidable) and operational prerequisites.
- `legacy-to-v8-mapping`: A mapping guide from legacy SOAP/REST v4 patterns to V8 routes for module CRUD, relationships, and metadata.
- `migration-and-parity-verification`: Repeatable checks to validate that migrated integrations behave correctly (data correctness, permissions, error handling, performance, rollback readiness).

### Modified Capabilities

- (none)

## Impact

- External systems: ETL/iPaaS, custom sync jobs, portals/frontends, telephony/marketing connectors, and any middleware currently calling `/soap.php` or `/service/v4*/rest.php`.
- SuiteCRM API surface: Increased reliance on `/Api/access_token` and `/Api/V8/*`; may require web server configuration to forward `Authorization` headers reliably.
- Operations/security: Requires stable OAuth2 key material and an `oauth2_encryption_key` configured; client credentials lifecycle (rotation, storage) becomes an explicit responsibility.
- Documentation: Integrator-facing guidance should be updated to prefer V8 and to document any legacy endpoint retirement policy.

## Installation / Migration Impact

- Ensure V8 API prerequisites are configured (OAuth2 keys present, encryption key set, and web server forwards `Authorization`).
- If legacy endpoints are eventually restricted/disabled, deployments must coordinate cutover timing with integration owners and provide rollback steps.

## Risks

- Unknown/undocumented integrations may break if legacy endpoints are retired without detection coverage.
- Behavioral gaps between legacy v4/SOAP and V8 (filters, field names, permissions, error formats) may require per-integration adaptation.
- OAuth2 client management and secret handling introduces new operational processes (rotation, least privilege, revocation).
- Network/proxy behavior may strip `Authorization` headers unless explicitly configured.

## Verification

- Demonstrate inventory completeness by correlating log-based detection with code/config searches and stakeholder confirmation.
- For each migrated integration, validate: authentication, key workflows, permissions, data correctness, and error handling against agreed acceptance criteria.
- Run a parallel period (where feasible) comparing legacy vs V8 outputs for critical workflows before cutover.
