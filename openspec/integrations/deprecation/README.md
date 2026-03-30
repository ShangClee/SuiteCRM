## Legacy Endpoint Deprecation

Date: 20260330

This folder contains operational guidance for retiring legacy endpoints:

- SOAP: `/soap.php` and versioned `service/*/soap.php`
- REST v4/v4_1: `/service/v4/rest.php` and `/service/v4_1/rest.php`

## Decision

Default approach: implement warn/enforce/disable controls operationally (reverse proxy/WAF), not in the SuiteCRM application code.

Rationale:

- Minimizes application risk and avoids introducing breaking changes in core request paths.
- Allows controlled rollout, environment-specific allowlists, and fast rollback.
- Keeps deprecation controls independent of application release cadence.

If future requirements demand in-app controls, implement them as a separately-scoped change.
