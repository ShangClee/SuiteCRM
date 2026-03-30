## ADDED Requirements

### Requirement: O(1) Relationship Queries in Lead Conversion Activities
The `handleActivities` method in `modules/Leads/views/view.convertlead.php` SHALL NOT execute relationship or user retrieval queries inside the loop over the lead's activities.

#### Scenario: Converting a lead with 50 activities
- **WHEN** the user converts a lead that has 50 related activities
- **THEN** the system fetches the related users for all 50 activities using a constant number of queries before iterating to copy them.
