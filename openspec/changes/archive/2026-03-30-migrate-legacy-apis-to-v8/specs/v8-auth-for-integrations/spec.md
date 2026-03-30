## ADDED Requirements

Date: 20260330

### Requirement: Integrations authenticate to V8 using OAuth2 bearer tokens
External integrations SHALL authenticate to the V8 API using OAuth2 access tokens and the `Authorization: Bearer <token>` header.

#### Scenario: Access a protected V8 endpoint
- **WHEN** an integration calls a `/Api/V8/*` endpoint with a valid bearer token
- **THEN** the request is authorized according to the token identity and permissions

### Requirement: Token acquisition uses the V8 access token endpoint
Integrations SHALL obtain tokens via `POST /Api/access_token` using a supported OAuth2 grant type.

#### Scenario: Acquire a token using client credentials
- **WHEN** a machine-to-machine integration requests a token via the client-credentials grant
- **THEN** the response provides an access token suitable for calling `/Api/V8/*`

### Requirement: Grant type selection follows integration context
The migration program SHALL select OAuth2 grant types according to integration context:

- Machine-to-machine service integration MUST use `client_credentials` unless user-context is strictly required.
- User-facing integrations MUST use `authorization_code` when user interaction is available.
- The `password` grant MUST be treated as a transitional mechanism and documented as such when used.

#### Scenario: Choose a grant type for a new integration
- **WHEN** an integration is planned for migration
- **THEN** the chosen grant type is recorded in the integration inventory record with justification

### Requirement: OAuth2 client credentials are managed and rotatable
OAuth2 client identifiers and secrets used by integrations SHALL be treated as credentials with an explicit storage and rotation process.

#### Scenario: Rotate a client secret
- **WHEN** a client secret rotation is initiated
- **THEN** the integration transitions to the new secret without service interruption and the old secret is invalidated

### Requirement: Token identity and access are least-privilege
Each integration SHALL be assigned the least privilege required to perform its documented use cases.

#### Scenario: Integration attempts an unauthorized operation
- **WHEN** an integration calls a V8 endpoint outside its permitted access scope
- **THEN** the API denies the request and the failure is observable for remediation

### Requirement: Authorization header must be preserved through HTTP infrastructure
Deployments SHALL ensure the `Authorization` header reaches the SuiteCRM V8 entrypoint for `/Api/*` requests.

#### Scenario: Verify header forwarding
- **WHEN** an integration environment is prepared for migration
- **THEN** a token-protected request succeeds without requiring non-standard header workarounds
