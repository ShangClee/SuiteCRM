## ADDED Requirements

### Requirement: Account Geocode Synchronization
The system must synchronize Geocoding information to related records when an Account is saved, relying on the map service settings.

#### Scenario: Hook is disabled
- **WHEN** the map service setting `logic_hooks_enabled` is false.
- **THEN** no related records should be updated or saved.

#### Scenario: Address changed for related project
- **WHEN** an Account is saved and it is related to a Project whose `jjwg_maps_address_c` does not match its `fetched_row`.
- **THEN** the Project's map data should be updated and saved.

#### Scenario: Address changed for related opportunity
- **WHEN** an Account is saved and it is related to an Opportunity whose address needs updating.
- **THEN** the Opportunity map data should be updated.
