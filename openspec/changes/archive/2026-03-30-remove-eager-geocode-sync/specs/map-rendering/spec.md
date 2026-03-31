## MODIFIED Requirements

### Requirement: Render map markers for related modules
The system SHALL display map markers for records in related modules (Cases, Opportunities, Projects) based on the geocoded coordinates of their parent Account, rather than looking for coordinates on the related records themselves.

#### Scenario: Displaying Cases on the map
- **WHEN** a user opens the `jjwg_Maps` module and selects to display "Cases"
- **THEN** the system queries Cases, dynamically joining the related Account's `accounts_cstm` table to fetch `jjwg_maps_lat_c` and `jjwg_maps_lng_c`
- **AND** the map successfully plots the Cases at the location of their parent Accounts.

#### Scenario: Displaying Opportunities on the map
- **WHEN** a user opens the `jjwg_Maps` module and selects to display "Opportunities"
- **THEN** the system queries Opportunities, dynamically joining the related Account's `accounts_cstm` table to fetch the coordinates
- **AND** the map successfully plots the Opportunities at the location of their parent Accounts.

## REMOVED Requirements

### Requirement: Eager Sync Geocodes to Related Modules
**Reason**: Syncing Account coordinates to all related Cases/Opportunities on every Account save is slow, fragile, and duplicates data unnecessarily. We are moving to a lazy lookup model.
**Migration**: Removed `AccountGeocodeService` and associated logic hooks. The map renderer now fetches this data dynamically at query time.
