## ADDED Requirements

Date: 20260330

### Requirement: Maintain an integration inventory record per external system
The program SHALL maintain exactly one inventory record per external system that integrates with SuiteCRM.

#### Scenario: Create a new inventory record
- **WHEN** an integration is identified by logs, code search, or stakeholder report
- **THEN** an inventory record is created for that integration

### Requirement: Inventory record contains mandatory discovery and migration fields
Each integration inventory record SHALL include the following fields:

- Owner and escalation contact
- Source system name and environment(s)
- Integration purpose and business criticality
- Legacy API surface in use (SOAP, REST v4, REST v4_1) and full endpoint URLs
- Authentication model (service account vs user-context) and credential storage location
- Modules, fields, and relationships accessed
- Data direction (read/write), frequency, peak volume, and SLA expectations
- Proposed V8 migration target (OAuth2 grant type, client identity, and V8 endpoints)
- Verification plan and rollback plan

#### Scenario: Inventory record validation
- **WHEN** an inventory record is marked ready for migration planning
- **THEN** all mandatory fields are present and non-empty

### Requirement: Track discovery evidence for each integration
Each inventory record SHALL include discovery evidence that supports its existence and current API usage.

#### Scenario: Evidence is attached from HTTP logs
- **WHEN** an integration is discovered via HTTP logs
- **THEN** the record includes the log source and example request path(s) showing legacy endpoint usage

#### Scenario: Evidence is attached from code/config repositories
- **WHEN** an integration is discovered via code/config search
- **THEN** the record includes the repository reference and the matched endpoint string(s)

### Requirement: Inventory completeness requires multi-source confirmation
Inventory completeness SHALL be asserted only after correlating at least two of the following sources: HTTP logs, code/config search, stakeholder confirmation.

#### Scenario: Declare inventory complete
- **WHEN** the migration program declares the inventory complete for a deployment scope
- **THEN** each integration record shows at least two independent confirmation sources

### Requirement: Maintain integration status lifecycle
Each inventory record SHALL have a status lifecycle with at least: discovered, assessed, planned, migrating, migrated, verified, retired.

#### Scenario: Mark an integration migrated
- **WHEN** an integration is cut over to V8 endpoints in production
- **THEN** its status is set to migrated and the legacy endpoints used previously are recorded as deprecated for that integration
