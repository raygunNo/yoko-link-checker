---
status: complete
priority: p2
issue_id: "042"
tags: [code-review, data-integrity, wordpress]
dependencies: []
---

# Uninstall and Deactivation Cleanup Gaps

## Problem Statement

Two cleanup gaps leave orphaned data:
1. `uninstall.php` cleans `yoko_lc_scan_%_cursor_%` but misses `yoko_lc_scan_%_last_activity` options created by `ScanOrchestrator`.
2. `Deactivator::cleanup_transients()` doesn't clean `yoko_lc_batch_lock_*` transients.

## Findings

**File:** `uninstall.php` line 62 — LIKE pattern misses `_last_activity`
**File:** `src/Deactivator.php` lines 96-99 — no batch lock cleanup
**File:** `src/Scanner/ScanOrchestrator.php` line 595 — writes `_last_activity` options

Found by: wp-php-reviewer, schema-drift-detector, wp-hooks-reviewer

## Proposed Solutions

### Option A: Broaden cleanup patterns (Recommended)
- **Approach:** Change uninstall LIKE to `'yoko_lc_scan_%'` (covers all scan-related dynamic options). Add batch lock transient cleanup to Deactivator.
- **Effort:** Small

## Technical Details
- **Affected files:** `uninstall.php`, `src/Deactivator.php`

## Acceptance Criteria
- [ ] `_last_activity` options cleaned on uninstall
- [ ] Batch lock transients cleaned on deactivation
- [ ] No orphaned plugin data remains after uninstall

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
