## ADDED Requirements

Date: 20260330

### Requirement: Provide a mapping guide from legacy APIs to V8 routes
The migration program SHALL provide a mapping guide that translates legacy SOAP and REST v4/v4_1 integration use cases to V8 endpoints.

#### Scenario: Migration planning references the mapping guide
- **WHEN** an integration is planned for migration
- **THEN** the mapping guide is used to identify the V8 endpoints and request shapes needed for the integration workflows

### Requirement: Mapping guide covers core module CRUD operations
The mapping guide SHALL include mappings for module CRUD operations, including listing, reading by id, creating, updating, and deleting, using the V8 module routes.

#### Scenario: Map a legacy record fetch to V8
- **WHEN** a legacy integration fetches a record by module and id
- **THEN** the guide maps that operation to `GET /Api/V8/module/{moduleName}/{id}`

### Requirement: Mapping guide covers relationship operations
The mapping guide SHALL include mappings for reading, adding, and removing relationships using the V8 relationships routes.

#### Scenario: Map a legacy relationship read to V8
- **WHEN** a legacy integration reads a linked collection for a record
- **THEN** the guide maps that operation to `GET /Api/V8/module/{moduleName}/{id}/relationships/{linkFieldName}`

### Requirement: Mapping guide covers metadata discovery
The mapping guide SHALL include mappings for discovering modules and fields via V8 metadata endpoints.

#### Scenario: Map legacy metadata discovery to V8
- **WHEN** an integration needs module/field metadata for dynamic behavior
- **THEN** the guide maps that need to `GET /Api/V8/meta/modules` and `GET /Api/V8/meta/fields/{moduleName}`

### Requirement: Mapping guide documents authentication changes
The mapping guide SHALL document the migration from session-based authentication to OAuth2 bearer tokens and the token acquisition flow.

#### Scenario: Replace legacy login/session usage
- **WHEN** a legacy integration previously performed a `login` call and reused a session id
- **THEN** the guide instructs replacing it with token acquisition via `POST /Api/access_token` and bearer token usage

### Requirement: Mapping guide defines error and retry expectations for integrations
The mapping guide SHALL define how integrations interpret common error conditions during migration, including authentication failures and permission denials.

#### Scenario: Handle invalid token errors
- **WHEN** an integration receives an authorization failure due to an invalid or expired token
- **THEN** the integration refreshes or reacquires a token according to the documented grant flow
