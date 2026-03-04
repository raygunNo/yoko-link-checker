---
status: complete
priority: p2
issue_id: "071"
tags: [code-review, wordpress, cron]
dependencies: ["056"]
---

# handle_auto_scan() Doesn't Check if Auto-Scan Is Enabled

## Problem Statement
`Plugin::handle_auto_scan()` unconditionally starts a scan when the `yoko_lc_auto_scan` cron hook fires. If the user disables auto-scanning but the cron event was already scheduled, the scan still executes.

## Findings
**File:** `src/Plugin.php` lines 165-167
Found by: wp-php-reviewer

## Proposed Solutions
### Option A: Add enabled check
- **Approach:** Check `get_option('yoko_lc_auto_scan_enabled', false)` at the start of `handle_auto_scan()`. Return early if disabled.
- **Effort:** Small

## Acceptance Criteria
- [ ] Auto-scan cron handler respects the enabled setting

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
