---
status: complete
priority: p3
issue_id: "083"
tags: [code-review, hooks, php]
dependencies: []
---

# Filter Values Lack Bounds Checking

## Problem Statement
`yoko_lc_batch_delay`, `yoko_lc_discovery_batch_size`, and `yoko_lc_checking_batch_size` filters accept any value without clamping. A filter returning 0 or negative values could cause unexpected behavior.

## Findings
**File:** `src/Scanner/ScanOrchestrator.php` lines 243, 276, 497
Found by: wp-hooks-reviewer

## Proposed Solutions
### Option A: Add min/max clamping after each filter
- **Approach:** `max(1, min((int) $batch_size, 500))` for sizes, `max(0, min((int) $delay, 300))` for delay.
- **Effort:** Small

## Acceptance Criteria
- [ ] All filtered values have reasonable bounds

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
