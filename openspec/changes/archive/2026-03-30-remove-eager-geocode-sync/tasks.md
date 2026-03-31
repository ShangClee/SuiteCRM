## 1. Remove Eager Sync Logic

- [x] 1.1 Remove `AccountGeocodeService.php` class entirely.
- [x] 1.2 Remove the Account `after_save` logic hook that triggers the geocode sync service.
- [x] 1.3 Verify Account saves no longer trigger background syncs.

## 2. Update Map Controller Query Builder

- [x] 2.1 In `modules/jjwg_Maps/controller.php`, identify where `create_new_list_query` is called for the map markers.
- [x] 2.2 Add a conditional block to check if the `$display_module` is a related module (Cases, Opportunities, Projects).
- [x] 2.3 For related modules, build the `$params` array with `custom_select` to fetch `accounts_cstm.jjwg_maps_lat_c`, `lng_c`, and `address_c`.
- [x] 2.4 For related modules, build the `$params` array with `custom_from` to JOIN the relationship table (e.g., `accounts_cases`), the `accounts` table, and the `accounts_cstm` table.
- [x] 2.5 For related modules, update the `$where_conds` to check `accounts_cstm` instead of the base module's custom table.
- [x] 2.6 Ensure standard modules (Accounts, Contacts, Leads) still query their own custom tables correctly.

## 3. Verify Map Rendering

- [x] 3.1 Test rendering Accounts on the map.
- [x] 3.2 Test rendering Cases on the map.
- [x] 3.3 Test rendering Opportunities on the map.
- [x] 3.4 Ensure PHP linting passes on `modules/jjwg_Maps/controller.php`.
