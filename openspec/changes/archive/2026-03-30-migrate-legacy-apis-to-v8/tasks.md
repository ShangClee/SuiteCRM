Date: 20260330

## 1. Inventory Foundations

- [x] 1.1 Add an integration inventory template and required fields document
- [x] 1.2 Add a standardized status lifecycle definition for integrations
- [x] 1.3 Add a location in-repo to store inventory records (non-secret content only)
- [x] 1.4 Add guidance for attaching discovery evidence to inventory records
- [x] 1.5 Verify inventory artifacts are consistent with `integration-inventory` spec

## 2. Legacy Usage Detection

- [x] 2.1 Implement a repeatable access-log analysis procedure (inputs, filters, outputs)
- [x] 2.2 Add a script or tooling to extract callers hitting `/soap.php` and `/service/v4*/rest.php`
- [x] 2.3 Add a repeatable repo search checklist for endpoint strings and common client libraries
- [x] 2.4 Connect detection output to inventory records (new record creation or evidence linking)
- [x] 2.5 Add a “zero legacy usage” verification procedure for retirement readiness
- [x] 2.6 Verify detection artifacts satisfy `legacy-api-usage-detection` spec

## 3. V8 OAuth2 Readiness and Integration Auth Patterns

- [x] 3.1 Document V8 prerequisites for deployments (keys, encryption key, header forwarding)
- [x] 3.2 Document supported grant types and selection rules for integration contexts
- [x] 3.3 Add example token acquisition flows for client-credentials and auth-code
- [x] 3.4 Add credential storage and rotation guidance for integration owners (no secrets in repo)
- [x] 3.5 Add a smoke-check procedure using `/Api/access_token` and `/Api/V8/current-user`
- [x] 3.6 Verify auth artifacts satisfy `v8-auth-for-integrations` spec

## 4. Legacy-to-V8 Endpoint Mapping Guide

- [x] 4.1 Create a mapping guide structure organized by use case (CRUD, relationships, metadata)
- [x] 4.2 Map legacy “login + session” flows to OAuth2 bearer token usage
- [x] 4.3 Add mappings for core module CRUD using `/Api/V8/module/*` routes
- [x] 4.4 Add mappings for relationship operations using `/Api/V8/module/{moduleName}/{id}/relationships/*`
- [x] 4.5 Add mappings for metadata discovery using `/Api/V8/meta/*` routes
- [x] 4.6 Add error-handling and retry guidance (invalid token, permission denied)
- [x] 4.7 Verify mapping artifacts satisfy `legacy-to-v8-mapping` spec

## 5. Migration Playbook and Parity Verification

- [x] 5.1 Create a per-integration migration checklist (plan, migrate, verify, cutover, rollback)
- [x] 5.2 Add an acceptance-criteria template for capturing integration workflows
- [x] 5.3 Add a parallel-run comparison procedure for integrations that can dual-run
- [x] 5.4 Add a standard rollback procedure while legacy endpoints remain enabled
- [x] 5.5 Add a parity-gap tracking format and remediation decision framework
- [x] 5.6 Define gates for marking an integration “verified” and for approving legacy retirement
- [x] 5.7 Verify migration artifacts satisfy `migration-and-parity-verification` spec

## 6. Optional: Legacy Deprecation Controls

- [x] 6.1 Decide whether to implement warn/enforce/disable phases in this repo or operationally
- [x] 6.2 If in-repo, implement a non-breaking “detect and warn” mechanism for legacy endpoints
- [x] 6.3 If in-repo, implement an allowlist/denylist enforcement option behind configuration
- [x] 6.4 Add operational runbooks for enabling and rolling back deprecation controls

## 7. Verification

- [x] 7.1 Lint any added PHP tooling with `php -l`
- [x] 7.2 Run the existing test suite for touched areas (PHPUnit) where applicable
- [x] 7.3 Validate documentation examples against the V8 Swagger and Postman artifacts
