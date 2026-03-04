---
status: complete
priority: p2
issue_id: "009"
tags: [code-review, data-integrity]
dependencies: []
---

# UrlRepository::delete() Doesn't Delete Associated Links

## Problem Statement
`UrlRepository::delete()` (line 218) only deletes the row from `yoko_lc_urls`. No foreign key constraints exist in the database schema, so there is no cascading delete. This leaves orphaned rows in `yoko_lc_links` where `url_id` points to a non-existent URL record. Over time, these orphaned links accumulate and can cause incorrect counts, broken joins, and data integrity issues throughout the plugin.

## Findings
- `UrlRepository::delete()` at lines 218-229 only performs `DELETE FROM yoko_lc_urls WHERE id = %d`.
- No corresponding delete of rows from `yoko_lc_links` where `url_id` matches the deleted URL.
- The database schema does not use foreign key constraints (common in WordPress plugins using MyISAM or InnoDB without explicit FK setup).
- Orphaned `yoko_lc_links` rows persist indefinitely.
- Any code that joins `yoko_lc_links` to `yoko_lc_urls` may produce incorrect results or empty URL data for orphaned links.

## Proposed Solutions

### Option A: Delete Associated Links Before URL Deletion
- **Approach:** Add a `DELETE FROM yoko_lc_links WHERE url_id = %d` query before the URL deletion in `UrlRepository::delete()`. Wrap both deletes in a transaction for atomicity.
- **Pros:** Simple and direct; prevents orphans; maintains data integrity; easy to understand
- **Cons:** Need to ensure transaction support; slightly slower delete operation
- **Effort:** Small
- **Risk:** Low

### Option B: Add Foreign Key Constraints with CASCADE DELETE
- **Approach:** Add actual foreign key constraints to the database schema with `ON DELETE CASCADE`.
- **Pros:** Database enforces referential integrity automatically; prevents orphans at the database level
- **Cons:** Requires schema migration; may not work with all MySQL configurations; WordPress convention is to not use FKs; migration complexity for existing installs
- **Effort:** Medium
- **Risk:** Medium

### Option C: Cleanup Orphans via Scheduled Maintenance
- **Approach:** Add a periodic cleanup job that deletes orphaned links where the associated URL no longer exists.
- **Pros:** No changes to delete logic; handles existing orphans too
- **Cons:** Does not prevent the problem, only mitigates it; orphans exist between cleanup runs; additional cron load
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Repository/UrlRepository.php` (lines 218-229)

## Acceptance Criteria
- [ ] Deleting a URL also deletes all associated links from `yoko_lc_links`
- [ ] Both deletes happen atomically (both succeed or both fail)
- [ ] No orphaned `yoko_lc_links` rows remain after a URL deletion
- [ ] Existing orphaned links from past deletions are cleaned up (either via migration or maintenance task)
- [ ] Delete operation correctly handles the case where a URL has zero associated links

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
