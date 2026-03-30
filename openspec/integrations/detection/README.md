## Legacy API Usage Detection

Date: 20260330

This folder provides procedures and tooling to detect usage of SuiteCRM legacy APIs:

- SOAP: `/soap.php` and `service/*/soap.php`
- REST v4/v4_1: `/service/v4/rest.php` and `/service/v4_1/rest.php`

Outputs from detection are used to:

- Create new inventory records under `openspec/integrations/inventory/records/`
- Attach discovery evidence to existing inventory records

See:

- `access-logs.md` for log-based detection procedure.
- `repo-search.md` for code/config search checklist.
- `retirement-readiness.md` for the “zero legacy usage” verification procedure.
