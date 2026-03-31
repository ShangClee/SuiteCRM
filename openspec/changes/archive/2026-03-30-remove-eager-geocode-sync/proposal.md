## Why

Currently, when an Account is saved and its geocoded coordinates are updated, an eager propagation mechanism immediately attempts to sync these coordinates to all related records (Cases, Opportunities, Projects, Meetings). This is problematic because it makes the Account save operation synchronous and slow, creates fragile sync logic if the related record count is large, and unnecessarily duplicates data across multiple tables. We want to shift from "eager propagation" to "lazy lookup", where coordinates are stored once on the Account, and the map rendering query joins to the Account to get the coordinates dynamically.

## What Changes

- **Removal of Eager Propagation Hooks**: Delete the logic hooks on Account save that propagate coordinates to Cases, Opportunities, and Projects.
- **Removal of Geocode Sync Service**: Remove the `AccountGeocodeService` and its related sync jobs that attempt to batch-update related records.
- **Dynamic Map Rendering Queries**: Modify the `jjwg_Maps` controller so that when rendering maps for Cases, Opportunities, or Projects, the query dynamically joins the related Account's `accounts_cstm` table to fetch `jjwg_maps_lat_c` and `jjwg_maps_lng_c`.
- **BREAKING**: Historical location data for Cases/Opportunities will no longer be preserved (they will always reflect the current Account location), and the redundant `jjwg_maps_lat_c`/`jjwg_maps_lng_c` custom fields on these related modules will be obsolete.

## Capabilities

### New Capabilities
- None.

### Modified Capabilities
- `map-rendering`: The map rendering capability for related modules (Cases, Opportunities, Projects) is changing its underlying data source from module-specific custom fields to dynamic inheritance from the related Account.

## Impact

- **Affected Code**: 
  - `modules/jjwg_Maps/controller.php` (Map rendering query builder)
  - `custom/modules/Accounts/logic_hooks.php` (Removal of sync hooks)
  - `custom/Extension/modules/Accounts/Ext/LogicHooks/` (Removal of sync hooks)
  - `modules/Accounts/AccountGeocodeService.php` (Removal)
- **Data Model**: The `jjwg_maps_*` custom fields on `cases_cstm`, `opportunities_cstm`, and `project_cstm` tables will no longer be used or needed.
- **Performance**: Account save performance will significantly improve. Map rendering will incur a slight overhead due to a 3-table JOIN.

## Goals

- Eliminate the N+1 query and slow synchronous save issues when updating an Account's address.
- Remove redundant data synchronization logic and jobs.
- Simplify the architecture by having a single source of truth for location data.

## Non-goals

- We are not changing how Accounts, Contacts, or Leads are directly geocoded.
- We are not refactoring the entire `jjwg_Maps` module, only the list query generation for related modules.

## Risks

- **Historical Accuracy**: As noted in the breaking changes, older Cases will visually move on the map if the Account changes its address. This is deemed acceptable for the current business use case.
- **Query Performance**: The new JOIN query in `jjwg_Maps` might be slow if the relationship tables are not properly indexed.

## Verification

- **Linting**: Ensure all changed PHP files pass `php -l`.
- **Manual Testing**: Verify that saving an Account no longer triggers sync jobs or slow saves. Verify that viewing a map of Cases or Opportunities successfully plots the points based on the related Account's coordinates.
