## Context

SuiteCRM handles relationships primarily through `Link2` and `SugarRelationship` classes, which default to single-bean retrieval methods (e.g., `getBeans()`, `get_linked_beans()`, `load_relationship()`). When these are invoked inside loops (e.g., processing a list of activities or rendering popup pickers), it triggers an N+1 query problem, severely degrading performance.

In the previous change, we introduced `BulkOperations` with `bulkRead` and `bulkAddRelatedIds`. To address new hotspots in `modules/Activities/Popup_picker.php` and `modules/Leads/views/view.convertlead.php`, we need to expand `BulkOperations` to support bulk reading of relationships (i.e., fetching related IDs for multiple parent beans at once) and apply it to these modules.

## Goals / Non-Goals

**Goals:**
- Extend `SuiteCRM\Utility\BulkOperations` with a `bulkGetLinkedBeans` or `bulkGetRelatedIds` method to retrieve relationship mappings for multiple parent IDs in a single query.
- Refactor `modules/Activities/Popup_picker.php` to fetch all related Contacts for Meetings, Calls, and Emails outside of their respective rendering loops.
- Refactor `modules/Leads/views/view.convertlead.php` to bulk-load related Users for activities before copying them, eliminating the per-activity `load_relationship("users")` calls.
- Provide pure PHPUnit coverage for the new bulk read methods.

**Non-Goals:**
- Completely rewriting the Popup_picker or Convert Lead architectures. We will only apply surgical replacements of the N+1 hotspots.
- Addressing every single `get_linked_beans` call in the entire system—only the specific, high-impact loops identified in the proposal.

## Decisions

**1. Expand `BulkOperations` for Relationship Reads**
- *Decision*: Add a `bulkGetRelatedIds(array $focusBeans, string $linkName): array` method to `BulkOperations.php`.
- *Rationale*: Instead of querying the database $N$ times for $N$ meetings to find their contacts, we can instantiate the relationship object once, extract the join table and keys, and perform a single `SELECT {lhs_key}, {rhs_key} FROM {table} WHERE {lhs_key} IN (...)` query. This returns a map of `[focus_id => [related_id_1, related_id_2]]` that can be cached in-memory.

**2. Surgical Refactoring in `Popup_picker.php`**
- *Decision*: Before the `foreach ($focus_meetings_list as $meeting)` loop, we will extract all meeting IDs. We will call `BulkOperations::bulkGetRelatedIds` to get the related Contact IDs. We will then perform a `BulkOperations::bulkRead('Contacts', $allContactIds)` to get the Contact beans. Finally, inside the loop, we will look up the pre-fetched contact instead of calling `$meeting->get_linked_beans('contacts')`.
- *Rationale*: This turns $O(N)$ queries into $O(1)$ (specifically, 2 queries: one for the link table, one for the Contacts table).

**3. Surgical Refactoring in `view.convertlead.php`**
- *Decision*: In `handleActivities`, before looping over `$activities`, we will extract all activity IDs and their modules. We will use the bulk utility to fetch related Users for all activities at once. Inside the loop, we will use the pre-fetched user mappings instead of `$activity->load_relationship("users")` and `$activity->users->getBeans()`.
- *Rationale*: Lead conversion can involve hundreds of historical activities. Bulk-fetching their assigned users prevents timeout issues.

## Risks / Trade-offs

- **Memory Consumption** → *Mitigation*: The `BulkOperations` utility will use chunking (e.g., 500 IDs at a time) for both the relationship queries and the subsequent Bean hydration to ensure we don't exceed PHP memory limits on large datasets.
- **Complex Relationship Logic (Roles, Custom Joins)** → *Mitigation*: We will reuse `SugarRelationship::getRelationshipTable()`, `getJoinKeyLHS()`, `getJoinKeyRHS()`, and `getRoleWhereClause()` inside our bulk method to ensure we strictly respect SuiteCRM's relationship metadata, including custom role fields.
