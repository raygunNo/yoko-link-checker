---
status: complete
priority: p2
issue_id: "008"
tags: [code-review, data-integrity, concurrency]
dependencies: ["007"]
---

# Race Condition in find_or_create (TOCTOU)

## Problem Statement
`UrlRepository::find_or_create()` (lines 132-152) has a time-of-check-to-time-of-use (TOCTOU) race condition. Two concurrent requests can both get `null` from `find_by_hash()`, and both then attempt to insert the same URL. The UNIQUE KEY constraint on `url_hash` prevents duplicate rows, but the second insert fails silently. Combined with the unchecked insert return value (issue #007), the second request ends up with a URL entity that has `id=0`, which then propagates to link records.

## Findings
- `UrlRepository::find_or_create()` at lines 132-152 performs a check-then-act pattern without any locking.
- Two concurrent requests can both pass the `find_by_hash()` check and both attempt to insert.
- The UNIQUE KEY on `url_hash` correctly prevents duplicate rows in the database.
- However, the second insert fails, and because the return value is not checked (issue #007), the entity gets `id=0`.
- This race is plausible during the scan discovery phase when multiple cron or AJAX requests process posts concurrently.

## Proposed Solutions

### Option A: Handle Insert Failure with Re-fetch
- **Approach:** After a failed `$wpdb->insert()`, attempt a `find_by_hash()` re-fetch. If the re-fetch succeeds, return the existing record. This pattern handles the race condition gracefully by treating "insert failed due to duplicate" as "someone else created it first."
- **Pros:** Simple to implement; no schema changes; handles the race naturally; works with existing UNIQUE KEY
- **Cons:** Relies on UNIQUE KEY being present; two queries on collision; does not prevent the race, just handles it
- **Effort:** Small
- **Risk:** Low

### Option B: INSERT ... ON DUPLICATE KEY UPDATE
- **Approach:** Use `INSERT ... ON DUPLICATE KEY UPDATE` to atomically insert or update, then retrieve the `insert_id` or the existing row's ID.
- **Pros:** Single atomic operation; no race possible; database handles concurrency
- **Cons:** Cannot use `$wpdb->insert()` (need raw query with `$wpdb->query()`); `LAST_INSERT_ID()` behavior differs for updates vs inserts; more complex implementation
- **Effort:** Medium
- **Risk:** Medium

### Option C: Database Advisory Lock
- **Approach:** Use `GET_LOCK()` / `RELEASE_LOCK()` around the find-or-create operation to serialize access.
- **Pros:** Eliminates the race entirely; can be applied to other critical sections
- **Cons:** Adds lock contention; potential for deadlocks; more complex; performance impact
- **Effort:** Medium
- **Risk:** High

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Repository/UrlRepository.php` (lines 132-152)

## Acceptance Criteria
- [ ] Concurrent `find_or_create()` calls for the same URL never produce entities with `id=0`
- [ ] Concurrent `find_or_create()` calls for the same URL always return a valid entity with a real database ID
- [ ] No duplicate URL rows are created (UNIQUE KEY constraint continues to be respected)
- [ ] The fix works correctly under concurrent cron and AJAX execution
- [ ] Error logging captures when a race condition is detected and handled

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
