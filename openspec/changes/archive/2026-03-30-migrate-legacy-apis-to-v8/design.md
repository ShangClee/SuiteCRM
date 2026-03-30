## Context

Date: 20260330

SuiteCRM currently exposes multiple API surfaces:

- Legacy SOAP entrypoints: `soap.php` and `service/*/soap.php` (session-based).
- Legacy REST v4/v4_1 entrypoints: `service/v4/rest.php` and `service/v4_1/rest.php` (session-based, RPC-style “method” calls).
  - Both are bootstrapped via `service/core/webservice.php` and implemented in `service/v4/SugarWebServiceImplv4.php` (and inherited variants).
  - Authentication returns a PHP session id via `SugarWebServiceImplv4::login()` and subsequent requests pass that session identifier.
- Modern V8 API entrypoint: `Api/index.php` → `Api/Core/app.php`.
  - Routes are defined in `Api/V8/Config/routes.php`.
  - OAuth2 server and resource server middleware are configured in `Api/V8/Config/services/middlewares.php`.

This change aims to audit all external integrations using the legacy SOAP/REST v4 APIs and migrate them to the V8 API, using OAuth2 bearer tokens.

Key operational constraints:

- Authorization header forwarding: `Api/Core/app.php` attempts to restore the `Authorization` header from `REDIRECT_HTTP_AUTHORIZATION`, implying web server configuration must preserve headers reliably.
- Crypto prerequisites: `Api/V8/Config/services/middlewares.php` depends on `$GLOBALS['sugar_config']['oauth2_encryption_key']` and OAuth2 key material (default paths in `Api/Core/Config/ApiConfig.php`).

Stakeholders:

- Integration owners (internal apps, ETL/iPaaS, middleware, portals).
- SuiteCRM admins/ops (Apache/Nginx config, keys, OAuth2 clients).
- Security/compliance (credential storage, least privilege, deprecation timelines).

## Goals / Non-Goals

**Goals:**

- Produce a complete inventory of external consumers of `soap.php` and `service/v4*/rest.php`.
- Standardize authentication for integrations on OAuth2 bearer tokens via `/Api/access_token`.
- Provide a repeatable migration and verification approach that prevents regressions.
- Enable a controlled retirement path for legacy APIs once usage reaches zero.

**Non-Goals:**

- Replace the entire legacy API implementation or refactor internal SuiteCRM business logic.
- Change module semantics beyond what is required to match existing integration behavior.
- Introduce new API frameworks beyond the existing V8 stack.

## Decisions

### 1) Treat this as a program with deliverables, not a single code change

**Decision:** The primary outputs are inventory, mappings, and a phased migration plan; code changes are optional and only introduced when required for observability, deprecation controls, or parity gaps.

**Rationale:** Most risk sits in unknown consumers and unverified behavior differences. A “move everything at once” approach is brittle without inventory and acceptance criteria.

**Alternatives considered:**

- Migrate opportunistically when integrations break (rejected: reactive and high downtime risk).
- Disable legacy endpoints immediately (rejected: unacceptable breaking risk without coverage).

### 2) Standard OAuth2 grant selection by integration type

**Decision:** Default to these grant types:

- Machine-to-machine backend integrations: `client_credentials`.
- User-facing apps requiring user-context: `authorization_code`.
- Use `password` only as a temporary bridge when the integration cannot support auth-code and must act in a user context.

**Rationale:** Matches what `Api/V8/Config/services/middlewares.php` supports while pushing toward least-privilege and revocable credentials.

**Alternatives considered:**

- Session-based reuse for “easy” migrations (rejected: keeps weakest mechanism).
- Introduce new grants or identity provider integration (out of scope for this change).

### 3) Use V8 “module” and “relationships” routes as the primary mapping surface

**Decision:** Map most legacy read/write operations to:

- `GET/POST/PATCH/DELETE /Api/V8/module/*` routes in `Api/V8/Config/routes.php`.
- Relationship operations under `/Api/V8/module/{moduleName}/{id}/relationships/*`.
- Metadata operations under `/Api/V8/meta/*` for schema and discovery.

**Rationale:** These routes represent the canonical V8 API surface and are documented via `Api/docs/swagger/swagger.json` and the Postman collection in `Api/docs/postman/`.

**Alternatives considered:**

- Custom “compatibility wrapper” endpoints under `/Api/V8/custom/*` for every integration (rejected: creates long-term maintenance debt; reserve for true parity gaps).

### 4) Establish a standard integration inventory record as the contract for migration

**Decision:** Every integration is represented by a single inventory record containing:

- Owner/contact, system purpose, environments.
- Legacy endpoints used (SOAP vs v4 vs v4_1), methods called, modules/fields touched.
- Auth model (service account vs user-context), credential storage, rotation policy.
- Data directionality, frequency, volume, and SLA/criticality.
- Migration target (grant type, V8 endpoints), verification plan, rollback.

**Rationale:** Without this contract, migrations become ad-hoc and impossible to coordinate or verify.

**Alternatives considered:**

- Track integrations only in a spreadsheet (allowed, but rejected as the only artifact: lacks enforceable structure and tends to drift).

### 5) Deprecation controls are optional but must be designed early

**Decision:** Design for deprecation as an optional phase:

- Phase 1: detect and report legacy usage.
- Phase 2: warn (headers/logs), communicate timelines.
- Phase 3: enforce restrictions (allowlist/denylist) and finally disable.

**Rationale:** Provides a reversible path, enabling gradual reduction to zero consumers before any breaking change.

**Alternatives considered:**

- No deprecation plan (rejected: legacy usage tends to persist indefinitely).

## Risks / Trade-offs

- [Incomplete discovery] → Mitigation: triangulate logs + repo searches + stakeholder sign-off before declaring inventory complete.
- [Behavior gaps between legacy and V8] → Mitigation: define per-integration acceptance criteria and add parity checks; use `/Api/V8/custom/*` only when gaps cannot be resolved otherwise.
- [Operational issues with Authorization headers through proxies] → Mitigation: document required server config; validate with a token-protected call early (e.g., `GET /Api/V8/current-user`).
- [OAuth2 secret management and rotation] → Mitigation: require explicit rotation and storage practices per integration; avoid embedding secrets in code; use least privilege.
- [Breaking impact if legacy endpoints are disabled] → Mitigation: treat disablement as a separate gate with explicit approval and rollback.

## Migration Plan

1) **Discovery and inventory**

- Identify legacy API usage by scanning access logs for:
  - `/soap.php`, `/service/v4/rest.php`, `/service/v4_1/rest.php`, and `service/*/soap.php`.
- Identify “silent” usage via code/config searches across known repos for those endpoints and for SOAP/v4 client libraries.
- Create an inventory record per integration and categorize by criticality and migration complexity.

2) **V8 readiness verification (SuiteCRM instance)**

- Validate V8 token issuance via `POST /Api/access_token` and protected access via `GET /Api/V8/current-user`.
- Verify OAuth2 keys and `oauth2_encryption_key` are configured and stable.
- Verify reverse proxy / web server preserves the `Authorization` header for `/Api/*` routes.

3) **Per-integration migration**

- Choose OAuth2 grant type (defaulting to client-credentials for service-to-service).
- Replace legacy method calls with V8 route calls (module CRUD, relationships, metadata).
- Normalize payload expectations and error handling in the integration.

4) **Parity validation and cutover**

- Run integration workflows in parallel (legacy vs V8) where feasible and compare outcomes.
- Cut over with rollback plan (ability to revert to legacy endpoints while still enabled).

5) **Legacy deprecation and retirement**

- Establish a “no consumers” threshold and validation period.
- Progress from detect → warn → enforce → disable, with explicit change management.

## Open Questions

- Which external systems are in scope for the first pilot migration (highest risk vs highest value)?
- Is there an existing log aggregation/SIEM available for request discovery, or must discovery be done via raw access logs on each node?
- Are there compliance requirements for OAuth2 client secret rotation intervals and storage mechanisms?
- Do we need to support any legacy v4-only behaviors that lack direct V8 equivalents (e.g., specific filters, compound calls), and if so, should they be addressed via V8 custom routes or by improving core V8 behavior?
