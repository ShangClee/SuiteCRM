## 1. Core Utilities

- [x] 1.1 Add `bulkGetRelatedIds` method to `lib/Utility/BulkOperations.php`.
- [x] 1.2 Add unit tests to `tests/unit/pure/Utility/BulkOperationsTest.php` to verify `bulkGetRelatedIds` correctly returns related mappings.

## 2. Refactor Activities Popup Picker

- [x] 2.1 Refactor `process_page` in `modules/Activities/Popup_picker.php` to collect all meeting, call, and email IDs into an array before looping over them.
- [x] 2.2 Call `BulkOperations::bulkGetRelatedIds` to get related Contact IDs for those activities.
- [x] 2.3 Call `BulkOperations::bulkRead` to fetch the actual Contact beans and store them in memory keyed by ID.
- [x] 2.4 Update the `Popup_picker.php` loops to lookup the pre-fetched Contacts instead of calling `$bean->get_linked_beans('contacts')`.

## 3. Refactor Lead Conversion

- [x] 3.1 Refactor `handleActivities` in `modules/Leads/views/view.convertlead.php` to collect all activity IDs and module types before the loop.
- [x] 3.2 Use `BulkOperations::bulkGetRelatedIds` to fetch all related User IDs for those activities in bulk.
- [x] 3.3 Update the `foreach` loop in `handleActivities` to use the pre-fetched User IDs instead of calling `load_relationship("users")` and `getBeans()`.

## 4. Verification

- [x] 4.1 Run PHP syntax lint (`php -l`) on `BulkOperations.php`, `Popup_picker.php`, and `view.convertlead.php`.
- [x] 4.2 Run `phpunit` to verify the `BulkOperationsTest.php` additions pass.
