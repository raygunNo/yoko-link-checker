---
status: complete
priority: p2
issue_id: "062"
tags: [code-review, performance, database]
dependencies: []
---

# get_status() Queries Pending URL COUNT on Every 3-Second Poll

## Problem Statement
`ScanOrchestrator::get_status()` is called by the AJAX poll handler every 3 seconds. During checking phase, it fires `SELECT COUNT(*) FROM wp_yoko_lc_urls WHERE status = 'pending'`. The scan record already maintains `total_urls` and `checked_urls` via `update_progress()`.

## Findings
**File:** `src/Scanner/ScanOrchestrator.php` lines 648-649
Found by: performance-oracle

## Proposed Solutions
### Option A: Use scan record fields instead of live COUNT
- **Approach:** Use `$scan->total_urls` and `$scan->checked_urls` for progress calculation. These are already maintained by `update_progress()`.
- **Effort:** Small

## Acceptance Criteria
- [ ] No live COUNT query during status polling
- [ ] Progress percentage still accurate

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
