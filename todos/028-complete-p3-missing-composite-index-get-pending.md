---
status: complete
priority: p3
issue_id: "028"
tags: [code-review, performance, database]
dependencies: []
---

# Missing Composite Index for get_pending() Query

## Problem Statement
`UrlRepository::get_pending()` (lines 246-258) executes a query with `WHERE status=%s AND is_ignored=0 AND id > %d ORDER BY id`. The current index covers only `(status, is_ignored)`, which does not include the `id` column used for cursor-based pagination and sorting. MySQL may need to perform a filesort operation for the `ORDER BY id` clause, degrading performance as the table grows.

## Findings
- `UrlRepository::get_pending()` (lines 246-258) uses `WHERE status=%s AND is_ignored=0 AND id > %d ORDER BY id`
- Current index on `yoko_lc_urls` table is `(status, is_ignored)` -- does not cover the `id` column
- Without `id` in the index, MySQL cannot use the index for both filtering and sorting
- This query runs during scan batch processing, potentially on large tables

## Proposed Solutions

### Option A: Add Composite Index (status, is_ignored, id)
- **Approach:** Add a composite index `(status, is_ignored, id)` to the `yoko_lc_urls` table in `Activator.php`. This allows MySQL to use the index for the full WHERE clause and ORDER BY without a filesort. Include a migration path for existing installations using `dbDelta()` or a schema version check.
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Activator.php` (table creation / schema definition)
  - `src/Repository/UrlRepository.php` (query reference)

## Acceptance Criteria
- [ ] Composite index `(status, is_ignored, id)` is added to `yoko_lc_urls` table schema
- [ ] Existing installations receive the new index on plugin update
- [ ] `get_pending()` query can be served entirely from the index (no filesort)
- [ ] No regressions in other queries that use the `(status, is_ignored)` index
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
