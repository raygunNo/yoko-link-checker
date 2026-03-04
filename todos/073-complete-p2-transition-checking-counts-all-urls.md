---
status: complete
priority: p2
issue_id: "073"
tags: [code-review, php, scanner]
dependencies: []
---

# transition_to_checking() Counts ALL URLs Instead of Pending

## Problem Statement
`transition_to_checking()` calls `$this->url_repository->count()` without a status filter, counting all URLs including already-checked ones from previous scans. This inflates `total_urls` and makes progress percentages inaccurate.

## Findings
**File:** `src/Scanner/ScanOrchestrator.php` line 331
Found by: performance-oracle, wp-php-reviewer

## Proposed Solutions
### Option A: Filter by pending status
- **Approach:** Change to `$this->url_repository->count(Url::STATUS_PENDING)`.
- **Effort:** Small

## Acceptance Criteria
- [ ] total_urls reflects only URLs needing checking in current scan

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
