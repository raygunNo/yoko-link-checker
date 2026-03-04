---
status: complete
priority: p2
issue_id: "007"
tags: [code-review, data-integrity, php]
dependencies: []
---

# Unchecked $wpdb->insert() Return Values

## Problem Statement
All three repository classes set `$entity->id = (int) $wpdb->insert_id` without verifying that the `$wpdb->insert()` call actually succeeded. When an insert fails, `$wpdb->insert()` returns `false` and `$wpdb->insert_id` remains 0 (or retains the value from the previous successful insert). This creates entities with `id=0`, which then propagate downstream -- for example, links get created with `url_id=0`, pointing to a non-existent URL record.

## Findings
- `UrlRepository` at line 172: Sets `$url->id = (int) $this->wpdb->insert_id` without checking insert return value.
- `LinkRepository` at line 138: Sets `$link->id = (int) $this->wpdb->insert_id` without checking insert return value.
- `ScanRepository` at line 208: Sets `$scan->id = (int) $this->wpdb->insert_id` without checking insert return value.
- No error handling or logging when insert operations fail.
- Downstream code assumes the entity ID is always valid after creation.

## Proposed Solutions

### Option A: Check Return Value and Return Null on Failure
- **Approach:** Check the return value of `$wpdb->insert()` (returns `false` on failure). If it fails, log the error and return `null` instead of the entity. Update all callers to handle a possible `null` return.
- **Pros:** Clean error propagation; prevents invalid entities from entering the system; enables proper error logging
- **Cons:** Requires updating all callers of create/save methods to handle null; moderate refactoring scope
- **Effort:** Medium
- **Risk:** Low

### Option B: Throw Exception on Insert Failure
- **Approach:** Throw a custom exception (e.g., `DatabaseException`) when `$wpdb->insert()` returns `false`. Catch at the caller level.
- **Pros:** Forces callers to handle errors; cannot be silently ignored; consistent with exception-based error handling
- **Cons:** Requires try/catch blocks at all call sites; more disruptive change
- **Effort:** Medium
- **Risk:** Medium

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Repository/UrlRepository.php` (line 172)
  - `src/Repository/LinkRepository.php` (line 138)
  - `src/Repository/ScanRepository.php` (line 208)

## Acceptance Criteria
- [ ] All `$wpdb->insert()` calls check the return value before using `$wpdb->insert_id`
- [ ] Failed inserts are logged with meaningful error messages including the table name and relevant data
- [ ] Failed inserts return `null` (or throw an exception) instead of an entity with `id=0`
- [ ] All callers of repository create/save methods handle the failure case
- [ ] No entities with `id=0` can be created in the system
- [ ] Existing tests pass (or are updated to reflect the new error handling)

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
