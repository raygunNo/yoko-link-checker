---
status: complete
priority: p2
issue_id: "043"
tags: [code-review, performance]
dependencies: []
---

# get_stats() AJAX Still Fires 6 Individual COUNT Queries

## Problem Statement

`AjaxHandler::get_stats()` calls `$this->url_repository->count()` 6 times individually instead of using the `get_status_counts()` GROUP BY query that was added for the dashboard.

## Findings

**File:** `src/Admin/AjaxHandler.php` lines 381-391

Found by: wp-php-reviewer

## Proposed Solutions

### Option A: Use get_status_counts() (Recommended)
- **Approach:** Replace 6 individual `count()` calls with single `get_status_counts()` call, compute total with `array_sum()`.
- **Effort:** Small

## Technical Details
- **Affected files:** `src/Admin/AjaxHandler.php`

## Acceptance Criteria
- [ ] Single GROUP BY query replaces 6 COUNT queries
- [ ] Response data is identical

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
