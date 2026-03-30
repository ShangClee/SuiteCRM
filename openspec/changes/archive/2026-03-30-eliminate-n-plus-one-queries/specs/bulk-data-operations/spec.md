## ADDED Requirements

### Requirement: Bulk read by id set
The system SHALL provide an internal bulk read capability that retrieves records for a given module by a provided set of ids in a set-based manner, minimizing per-record retrieval operations.

#### Scenario: Bulk read returns only requested records
- **WHEN** a caller requests records for module M with id set S
- **THEN** the system returns records whose ids are in S and excludes records not in S

#### Scenario: Bulk read supports chunking for large id sets
- **WHEN** a caller requests records with an id set that exceeds the configured chunk size
- **THEN** the system processes the request in chunks and returns a single aggregated result set

### Requirement: Bulk field update by id set
The system SHALL provide an internal bulk update capability that updates one or more fields for an id set using set-based database operations.

#### Scenario: Bulk update modifies matching records
- **WHEN** a caller requests a bulk update of fields F on module M for id set S
- **THEN** the system updates fields F for records with ids in S and leaves other records unchanged

#### Scenario: Bulk update rejects invalid ids
- **WHEN** a caller requests a bulk update with an id set containing invalid ids
- **THEN** the system does not apply updates for invalid ids and reports which ids were rejected

### Requirement: Bulk relationship add/remove
The system SHALL provide an internal bulk relationship write capability that adds and removes relationships for many related ids without requiring per-related-id bean retrieval of the same parent record.

#### Scenario: Bulk add creates or re-enables relationship links
- **WHEN** a caller requests adding relationships between parent record P and related id set S for relationship R
- **THEN** the system ensures each (P, s) link exists and is active for all s in S

#### Scenario: Bulk remove disables relationship links
- **WHEN** a caller requests removing relationships between parent record P and related id set S for relationship R
- **THEN** the system ensures each (P, s) link is inactive for all s in S

### Requirement: Relationship semantics are preserved
The system SHALL preserve SuiteCRM relationship table semantics, including soft delete flags and any configured role column constraints, for bulk relationship operations.

#### Scenario: Soft delete semantics are used for relationship removal
- **WHEN** a caller requests removing relationships for relationship R that uses soft delete semantics
- **THEN** the system marks relationship links as deleted rather than hard-deleting rows

#### Scenario: Role constraints are respected
- **WHEN** a relationship R defines a role column constraint
- **THEN** bulk relationship operations only affect links that match the role constraint

### Requirement: Behavior-preserving mode is available
The system SHALL support a behavior-preserving mode for bulk operations where logic hooks and permission checks are required by business invariants.

#### Scenario: Behavior-preserving mode applies invariant enforcement
- **WHEN** a caller executes a bulk operation in behavior-preserving mode
- **THEN** the system enforces the relevant invariants for that operation, including required permission checks and hook-triggered behavior
