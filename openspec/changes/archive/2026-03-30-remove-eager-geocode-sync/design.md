## Context

Currently, the `jjwg_Maps` module assumes that every record it plots (Account, Contact, Case, Opportunity, Project, etc.) has its own `jjwg_maps_lat_c` and `jjwg_maps_lng_c` custom fields. To support this, when an Account is saved and geocoded, an eager sync process (via `AccountGeocodeService` and logic hooks) updates these custom fields on all related Cases, Opportunities, and Projects. This is synchronous, slow, and duplicates data.

We want to eliminate this write-path complexity by shifting to a "lazy lookup" approach on the read-path. When the Map module asks to plot Cases, it should dynamically join the related Account's geocode data instead of relying on the Case's own custom fields.

## Goals / Non-Goals

**Goals:**
- Eliminate the need to sync geocode data from Accounts to related records.
- Remove all sync-related logic hooks and background jobs for geocoding.
- Modify the Map rendering query builder in `jjwg_Maps` to dynamically join the `accounts_cstm` table for modules that inherit their location from an Account.

**Non-Goals:**
- We are not changing how Accounts, Contacts, or Leads are directly geocoded.
- We are not touching the Google Maps API integration itself.

## Decisions

1. **Inject Custom JOINs into `create_new_list_query`**
   The `jjwg_Maps` controller (`modules/jjwg_Maps/controller.php`) builds its query using `$this->display_object->create_new_list_query()`. This SugarBean method accepts a `$params` array where we can inject `custom_select` and `custom_from`.
   For modules like Cases, Opportunities, and Projects, we will detect them in the map controller and inject a JOIN to their relationship table (e.g., `accounts_cases`), then to the `accounts` table, and finally to `accounts_cstm`.

2. **Remove Sync Logic Hooks**
   We will delete `custom/modules/Accounts/logic_hooks.php` (or the relevant extension files) that trigger the `AccountGeocodeService`. The `AccountGeocodeService.php` file itself will be deleted.

3. **Handle Edge Cases in the Map Controller**
   The controller uses `$this->display_object->table_name . "_cstm.jjwg_maps_lat_c"` in its WHERE clause. We will need to dynamically adjust this table alias in the WHERE clause based on whether we are using direct geocodes (Accounts) or inherited geocodes (Cases, via `accounts_cstm`).

## Risks / Trade-offs

- [Risk] **Query Performance:** A 3-table JOIN (Case -> Account_Case -> Account -> Account_Cstm) might be slower than a direct table read.
  → Mitigation: These relationship tables are heavily indexed in SuiteCRM by default, so performance impact should be negligible compared to the massive improvement in Account save times.
- [Risk] **List View Map Markers:** If users expect to see Cases on the map based on historical locations (where the Account *used* to be), this will break that expectation.
  → Mitigation: Business logic dictates the Account's current location is the source of truth.

## Migration Plan

1. Deploy the updated `modules/jjwg_Maps/controller.php`.
2. Remove the logic hooks and `AccountGeocodeService`.
3. (Optional, future cleanup) Run a database migration to drop `jjwg_maps_lat_c` and `jjwg_maps_lng_c` from `cases_cstm`, `opportunities_cstm`, and `project_cstm`. We won't do this immediately to ensure easy rollback.
