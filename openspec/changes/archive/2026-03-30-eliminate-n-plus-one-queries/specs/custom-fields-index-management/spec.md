## ADDED Requirements

### Requirement: Identify custom-field query patterns
The system SHALL support identifying which custom fields participate in filtering and joining patterns that commonly involve `_cstm` tables.

#### Scenario: Custom-field filter participation is captured
- **WHEN** a deployment selects a set of user-facing flows for optimization (e.g., list views, reports, API endpoints)
- **THEN** the system provides a way to enumerate the custom fields used for filtering/joining in those flows

### Requirement: Provide index recommendations for `_cstm` tables
The system SHALL provide index recommendations for `_cstm` tables that are aligned to the observed filter/join patterns.

#### Scenario: Composite index recommendation includes join key and filter field
- **WHEN** a query pattern joins module base table to its `_cstm` table and filters by a custom field C
- **THEN** the recommended index covers the join key and field C in a composite index where appropriate

#### Scenario: Recommendation avoids unnecessary indexes
- **WHEN** a custom field is not used in filtering or joining for the targeted flows
- **THEN** the system does not recommend creating an index solely for that field

### Requirement: Index changes are deployable and reversible
The system SHALL provide a deployable migration artifact for applying the recommended indexes and a rollback artifact for removing them.

#### Scenario: Migration artifact applies indexes
- **WHEN** an operator applies the migration artifact on an existing deployment
- **THEN** the recommended indexes are created on the target tables

#### Scenario: Rollback artifact removes indexes
- **WHEN** an operator applies the rollback artifact
- **THEN** the indexes introduced by the migration artifact are removed

### Requirement: Index changes do not alter functional behavior
Index management SHALL NOT change application feature behavior and SHALL only affect query planning and performance.

#### Scenario: Feature outputs remain equivalent after indexing
- **WHEN** indexes are added or removed according to the index management capability
- **THEN** feature outputs for the targeted flows remain functionally equivalent
