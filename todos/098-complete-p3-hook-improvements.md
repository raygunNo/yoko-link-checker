---
status: complete
priority: p3
issue_id: "098"
tags: [code-review, wordpress, hooks]
dependencies: []
---

# Hook Registration Improvements

## Problem Statement
Several minor hook registration issues:
1. `cron_schedules` filter registered after `maybe_run_activation()` which can fail, leaving cron schedules unrecognized.
2. `handle_auto_scan` does not explicitly declare 0 accepted args.
3. `yoko_lc_link_ignored/unignored` hooks pass link_id but the operation is on url_id, making hooks less useful for consumers.

## Findings
**File:** `src/Plugin.php` lines 324, 367
**File:** `src/Admin/AjaxHandler.php` lines 1651, 1690
Found by: wp-hooks-reviewer

## Proposed Solutions
### Option A: Fix all three
- **Approach:** Move cron_schedules registration before maybe_run_activation(). Add explicit `10, 0` args to auto_scan action. Pass both link_id and url_id to ignore/unignore hooks.
- **Effort:** Small

## Acceptance Criteria
- [ ] cron_schedules always registered even if activation fails
- [ ] Ignore/unignore hooks pass both IDs

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
