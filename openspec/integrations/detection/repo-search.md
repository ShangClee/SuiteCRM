## Repo Search Checklist (Legacy API Usage)

Date: 20260330

Goal: find “silent” usage of legacy APIs that may not show up clearly in access logs (batch jobs, middleware, iPaaS config, infrastructure code).

## Search Targets

Endpoint strings:

- `soap.php`
- `/soap.php`
- `service/v4/rest.php`
- `service/v4_1/rest.php`
- `service/v4/rest.php?`
- `service/v4_1/rest.php?`

Common implementation hints:

- `nusoap`
- `SoapClient`
- `SugarRestService`
- `SugarWebServiceImplv4`
- `rest.php` with `method=` payloads
- `login` returning a `session` id for subsequent requests

## Where to Search

- Application repos (ETL, portals, middleware)
- iPaaS exports (workflow JSON/YAML, connector configs)
- Infrastructure repos (reverse proxy rules, scheduled jobs, server configs)
- Runbooks and operational scripts (cron wrappers, data exports)

## Evidence Capture

For each match, capture:

- Repository name and branch/tag
- File path(s) and a short snippet showing the endpoint usage
- The owning team/system and the likely integration purpose

Then:

- Create or update an inventory record under `openspec/integrations/inventory/records/`
- Attach this evidence under the record’s Discovery Evidence section
