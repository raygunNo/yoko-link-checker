---
status: complete
priority: p3
issue_id: "050"
tags: [code-review, performance]
dependencies: []
---

# CSV Export OFFSET Pagination and Per-Row fflush

## Problem Statement

1. `stream_for_export()` uses `LIMIT/OFFSET` which degrades O(n^2) on large datasets.
2. `ResultsPage::handle_export()` calls `fflush()` on every row — 100K rows = 100K system calls.

## Findings

**File:** `src/Repository/LinkRepository.php` lines 618-634
**File:** `src/Admin/ResultsPage.php` line 291

Found by: performance-oracle, wp-php-reviewer

## Proposed Solutions

### Option A: Keyset pagination + batch fflush
- **Approach:** Use `WHERE l.id > %d ORDER BY l.id ASC LIMIT %d` instead of OFFSET. Flush every 500 rows.
- **Effort:** Small

## Acceptance Criteria
- [ ] Constant query performance regardless of offset depth
- [ ] fflush frequency reduced to every 500 rows

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
