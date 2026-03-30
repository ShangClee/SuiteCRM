## V8 OAuth2 for Integrations

Date: 20260330

This document describes how external systems authenticate to the SuiteCRM V8 API.

## Prerequisites (Deployment)

V8 API entrypoint and routes:

- Token endpoint: `POST /Api/access_token`
- Protected endpoints: `/Api/V8/*`

Prerequisites to validate:

- OAuth2 key material is present and readable by the web server process.
- `oauth2_encryption_key` is set in SuiteCRM configuration and is stable per deployment.
- HTTP infrastructure forwards the `Authorization` header for `/Api/*` requests.

## Supported Grant Types and Selection Rules

Supported grants are configured by the V8 middleware layer.

Grant selection rules:

- Machine-to-machine integrations MUST use `client_credentials` unless user context is strictly required.
- User-facing applications MUST use `authorization_code` when user interaction is available.
- `password` MUST be treated as transitional; record justification and an exit plan.

Record the chosen grant type and rationale in the integration inventory record.

## Token Acquisition Examples

### Client Credentials Grant

Request:

- Method: `POST`
- Path: `/Api/access_token`
- Content-Type: `application/x-www-form-urlencoded`

Parameters:

- `grant_type=client_credentials`
- `client_id=<id>`
- `client_secret=<secret>`

Example:

```bash
curl -sS -X POST "$SUITECRM_BASE_URL/Api/access_token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "grant_type=client_credentials" \
  --data-urlencode "client_id=$CLIENT_ID" \
  --data-urlencode "client_secret=$CLIENT_SECRET"
```

### Authorization Code Grant (High Level)

This flow is for user-facing apps that can redirect users:

1) The user is redirected to the authorization endpoint to approve access.
2) The app receives an authorization code.
3) The app exchanges the code for an access token via `POST /Api/access_token`.

Record the redirect URIs and client configuration outside of this repository.

## Calling V8 Endpoints

Include the bearer token:

```bash
curl -sS "$SUITECRM_BASE_URL/Api/V8/module/Accounts?fields[Accounts]=name&page[size]=1" \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

## Credential Storage and Rotation

Requirements:

- Do not store client secrets in this repository.
- Store credentials in an approved secret manager or environment-specific secure store.
- Define a rotation policy per integration and validate a rotation procedure before production cutover.
- Ensure revoked/rotated credentials are invalidated promptly.

## Smoke Check Procedure

Goal: validate V8 token issuance and Authorization header forwarding before migrating an integration.

1) Acquire a token using `POST /Api/access_token` for the chosen grant type.
2) Call a protected V8 endpoint (example: `GET /Api/V8/module/Accounts`) with `Authorization: Bearer <token>`.
3) If token acquisition succeeds but protected requests fail:
   - Validate that the `Authorization` header reaches SuiteCRM for `/Api/*`.
   - Validate the OAuth2 keys and encryption key configuration.
