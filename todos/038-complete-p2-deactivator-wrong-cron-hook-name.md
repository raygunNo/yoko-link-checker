---
status: complete
priority: p2
issue_id: "038"
tags: [code-review, wordpress, hooks]
dependencies: []
---

# Deactivator Clears Wrong Cron Hook Name

## Problem Statement

`Deactivator::deactivate()` clears `yoko_lc_scheduled_scan` but the actual hook name is `yoko_lc_auto_scan` (used in `ScanOrchestrator::maybe_schedule_next_scan()`). On deactivation, scheduled auto-scan events persist in the cron table.

## Findings

**File:** `src/Deactivator.php` line 49 — `wp_clear_scheduled_hook( 'yoko_lc_scheduled_scan' );`
**File:** `src/Scanner/ScanOrchestrator.php` line 517 — `$hook = 'yoko_lc_auto_scan';`
**File:** `uninstall.php` line 89 — correctly clears `yoko_lc_auto_scan`

Found by: wp-hooks-reviewer

## Proposed Solutions

### Option A: Fix hook name (Recommended)
- **Approach:** Change `'yoko_lc_scheduled_scan'` to `'yoko_lc_auto_scan'` in Deactivator.
- **Effort:** Small

## Technical Details
- **Affected files:** `src/Deactivator.php`

## Acceptance Criteria
- [ ] Deactivator clears `yoko_lc_auto_scan` hook
- [ ] No orphaned cron events after deactivation

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
