## Legacy-to-V8 Mapping Guide

Date: 20260330

This guide maps common legacy integration patterns to the SuiteCRM V8 API.

## Authentication Mapping

Legacy pattern:

- `login` returns a PHP session id
- subsequent calls send the session id as a parameter

V8 pattern:

- obtain an OAuth2 access token via `POST /Api/access_token`
- call V8 endpoints using `Authorization: Bearer <token>`

## Module CRUD

Base route group: `/Api/V8/module`

Common mappings:

- List/query records:
  - `GET /Api/V8/module/{moduleName}`
- Read record by id:
  - `GET /Api/V8/module/{moduleName}/{id}`
- Create record:
  - `POST /Api/V8/module`
- Update record:
  - `PATCH /Api/V8/module`
- Delete record:
  - `DELETE /Api/V8/module/{moduleName}/{id}`

Notes:

- Use V8 module names and field names as defined by V8 metadata endpoints.
- Ensure the OAuth2 token identity has the required module/field permissions.

## Relationships

Relationship routes live under:

- `/Api/V8/module/{moduleName}/{id}/relationships/*`

Common mappings:

- Read linked collection:
  - `GET /Api/V8/module/{moduleName}/{id}/relationships/{linkFieldName}`
- Add related record(s) (generic):
  - `POST /Api/V8/module/{moduleName}/{id}/relationships`
- Add related record(s) for a specific link:
  - `POST /Api/V8/module/{moduleName}/{id}/relationships/{linkFieldName}`
- Remove relationship:
  - `DELETE /Api/V8/module/{moduleName}/{id}/relationships/{linkFieldName}/{relatedBeanId}`

## Metadata Discovery

Use metadata endpoints for dynamic integrations and for validating mapping assumptions:

- Modules:
  - `GET /Api/V8/meta/modules`
- Fields for a module:
  - `GET /Api/V8/meta/fields/{moduleName}`

## Error Handling and Retry Guidance

Auth failures:

- `401 Unauthorized`: token missing/invalid/expired
  - reacquire a token (or refresh where supported by the chosen grant)

Permission failures:

- `403 Forbidden`: token identity lacks access
  - validate least-privilege decisions and module/field permissions

Availability and transient failures:

- `429 Too Many Requests` or `503 Service Unavailable` (if present via infra)
  - apply exponential backoff with jitter and a bounded retry budget

Non-retryable failures:

- `400 Bad Request`: request shape/parameters invalid
- `404 Not Found`: incorrect module name, id, or link field
  - treat as integration bug unless the workflow expects absent data

## Verification Checklist for a Mapping

- Confirm endpoint exists in V8 Swagger and routes.
- Confirm required module/field/relationship access under the chosen token identity.
- Confirm expected data shape for the integration workflow.
