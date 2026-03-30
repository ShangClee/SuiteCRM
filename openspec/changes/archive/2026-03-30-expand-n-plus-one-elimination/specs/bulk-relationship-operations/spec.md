## ADDED Requirements

### Requirement: Bulk Retrieve Relationship IDs
The `BulkOperations` utility SHALL provide a mechanism to fetch relationship target IDs for an array of source IDs in a single query.

#### Scenario: Fetching contacts for multiple meetings
- **WHEN** provided an array of Meeting IDs and the 'contacts' link name
- **THEN** it returns a mapping of Meeting ID to an array of related Contact IDs using a single database query.
