---
status: complete
priority: p3
issue_id: "033"
tags: [code-review, data-integrity]
dependencies: []
---

# TRUNCATE TABLE Without Error Handling

## Problem Statement
`AjaxHandler::clear_data()` (lines 459-463) issues 3 `TRUNCATE TABLE` statements without checking return values or wrapping them in a transaction. `TRUNCATE` requires the `DROP` privilege, which some shared hosting environments restrict. If any statement fails partway through, the database is left in an inconsistent state with some tables cleared and others not.

## Findings
- `AjaxHandler::clear_data()` (lines 448-473) executes 3 sequential `TRUNCATE TABLE` statements
- No return value checking on any of the `TRUNCATE` calls
- No transaction wrapping to ensure atomicity
- `TRUNCATE TABLE` requires `DROP` privilege, which may not be available on all hosting environments
- Partial failure (e.g., first table truncated, second fails) leaves data in an inconsistent state

## Proposed Solutions

### Option A: Use DELETE FROM with Transaction Wrapping
- **Approach:** Replace `TRUNCATE TABLE` with `DELETE FROM` (which requires only `DELETE` privilege) and wrap all three statements in a transaction. Check each statement's return value and roll back on failure. Report success or failure to the user.
- **Effort:** Small

### Option B: Keep TRUNCATE but Add Error Handling
- **Approach:** Keep `TRUNCATE TABLE` for performance but check the return value of each statement. If any fails, log the error and return a descriptive error message to the user. Note that `TRUNCATE` causes an implicit commit in MySQL, so true transaction wrapping is not possible with `TRUNCATE`.
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Admin/AjaxHandler.php` (lines 448-473)

## Acceptance Criteria
- [ ] All table-clearing statements have their return values checked
- [ ] Failure of any statement is reported to the user with a meaningful error message
- [ ] Partial failure does not leave the database in an inconsistent state (or the inconsistency is detected and reported)
- [ ] The operation works on hosts that restrict the `DROP` privilege (if using `DELETE FROM`)
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
