## ADDED Requirements

### Requirement: O(1) Relationship Queries in Popup Picker
The `process_page` method in `modules/Activities/Popup_picker.php` SHALL NOT execute database queries inside the loops over tasks, meetings, calls, and emails.

#### Scenario: Rendering the popup picker with 50 meetings
- **WHEN** the user loads the Activities popup picker for a record with 50 related meetings
- **THEN** the system executes a constant number of queries (e.g., 1 or 2) to fetch all related Contacts for those 50 meetings, rather than 50 individual queries.
