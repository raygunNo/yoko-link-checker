---
status: complete
priority: p1
issue_id: "056"
tags: [code-review, wordpress, hooks, cron]
dependencies: []
---

# Settings Save Does Not Sync Auto-Scan Cron Schedule

## Problem Statement
When the user changes `yoko_lc_auto_scan_enabled` from true to false (or changes scan frequency) in Settings, `handle_settings_save()` writes new option values but never calls `wp_clear_scheduled_hook('yoko_lc_auto_scan')`. If a scan already scheduled the next auto-scan event, disabling auto-scan in Settings won't prevent it from firing. Changing frequency also has no immediate effect.

## Findings
**File:** `src/Admin/AdminController.php` lines 247-285
Found by: wp-hooks-reviewer

The auto-scan cron is only scheduled by `ScanOrchestrator::maybe_schedule_next_scan()` after a scan completes. There is no sync between settings save and cron state.

## Proposed Solutions

### Option A: Clear and reschedule cron on settings save
- **Approach:** After updating auto-scan options in `handle_settings_save()`, call `wp_clear_scheduled_hook('yoko_lc_auto_scan')`. If auto-scan is still enabled, schedule a new event with the updated frequency.
- **Effort:** Small

## Acceptance Criteria
- [ ] Disabling auto-scan immediately clears scheduled cron event
- [ ] Changing frequency reschedules with new interval

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
