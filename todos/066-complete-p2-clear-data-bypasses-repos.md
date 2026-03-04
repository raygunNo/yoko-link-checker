---
status: complete
priority: p2
issue_id: "066"
tags: [code-review, architecture, php]
dependencies: []
---

# clear_data() Bypasses Repository Layer With Raw SQL

## Problem Statement
`AjaxHandler::clear_data()` directly accesses `$wpdb` and hardcodes table names, bypassing the Repository pattern used everywhere else. Table name knowledge is duplicated, and any future repository changes (soft-delete, audit logging) will be silently bypassed.

## Findings
**File:** `src/Admin/AjaxHandler.php` lines 436-470
Found by: architecture-strategist

## Proposed Solutions
### Option A: Add delete_all() to repositories
- **Approach:** Add `delete_all()` or `truncate()` methods to each repository. Create a coordinating method that orchestrates the transactional wipe using repository methods.
- **Effort:** Medium

### Option B: Use TRUNCATE for performance
- **Approach:** Replace DELETE with TRUNCATE TABLE (O(1) vs O(n)), but still centralize table names.
- **Effort:** Small

## Acceptance Criteria
- [ ] clear_data uses repository methods or centralized table references
- [ ] Transaction safety maintained

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
