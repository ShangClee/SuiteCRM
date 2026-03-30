# Proposal: Expand N+1 Elimination

## Context
In the previous change (`eliminate-n-plus-one-queries`), we built the `BulkOperations` utility with primitives to support bulk reads and updates, and applied it to the `FP_events` hotspot. However, scanning the codebase revealed several other significant N+1 hotspots that heavily impact performance, particularly in core activities and lead conversion modules.

## Problem
Currently, several critical paths in the application suffer from severe N+1 query performance degradation due to relationship and bean hydration inside loops:
1. **`modules/Activities/Popup_picker.php`**: When rendering the activities popup, it loops over tasks, meetings, calls, and emails. Inside these loops, it calls `$meeting->get_linked_beans('contacts', 'Contact')` (and similarly for calls and emails). This triggers a database query for *every single activity* to find its related contact.
2. **`modules/Leads/views/view.convertlead.php`**: During lead conversion, `handleActivities` gets all related activities, iterates through them, and individually calls `BeanFactory::newBean()`, `load_relationship("users")`, and `$activity->users->getBeans()` per activity, causing an explosion of database calls proportional to the number of activities.

## Proposed Solution
We propose to expand the use of our `BulkOperations` primitives to these newly identified hotspots:
1. **Enhance `BulkOperations`**: Add a `bulkGetLinkedBeans` or `bulkGetRelatedIds` helper that accepts an array of source beans/IDs and fetches the related target beans/IDs in a single chunked query (e.g., fetching all related contacts for a list of meetings).
2. **Refactor `Popup_picker.php`**: Pre-fetch all related contacts for meetings, calls, and emails using the bulk helper before the loop, and assign them in-memory.
3. **Refactor `view.convertlead.php`**: Pre-fetch users and related records for the lead's activities before the loop to eliminate the per-activity queries.

## Value/Impact
- **Performance**: Drastically reduces query count on the Activities popup and Lead conversion flows, turning `O(N)` queries into `O(1)` or `O(M)` (where M is chunk size).
- **Scalability**: Allows users to load popups and convert leads with hundreds of activities without hitting query ceilings or timing out.
- **Maintainability**: Further hardens and proves the `BulkOperations` utility as the standard way to handle collections of beans in SuiteCRM.
