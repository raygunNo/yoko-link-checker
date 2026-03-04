---
status: pending
priority: p2
issue_id: "094"
tags: [code-review, php, quality]
dependencies: []
---

# strtotime() Return Value Not Validated

## Problem Statement
`strtotime()` returns `false` on failure. Multiple locations pass the return value directly to `human_time_diff()` or use it in arithmetic without checking for `false`. Malformed dates from corrupt data could produce incorrect output or PHP type errors.

## Findings
**File:** `src/Admin/DashboardPage.php` lines 1987, 2008-2009
**File:** `src/Admin/LinksListTable.php` line 2790
**File:** `templates/admin/dashboard.php` line 171
Found by: wp-php-reviewer

## Proposed Solutions
### Option A: Add false checks
- **Approach:** Check `strtotime()` return for `false` and return a fallback (e.g., "Unknown" or em-dash) at each call site.
- **Effort:** Small

## Acceptance Criteria
- [ ] All strtotime() calls validated before use
- [ ] Graceful fallback for malformed dates

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
