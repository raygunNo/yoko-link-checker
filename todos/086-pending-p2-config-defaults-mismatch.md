---
status: pending
priority: p2
issue_id: "086"
tags: [code-review, php, configuration]
dependencies: []
---

# Configuration Defaults Mismatch (check_timeout 8 vs 30)

## Problem Statement
Default values for `yoko_lc_check_timeout` differ across the codebase. `Activator::set_default_options()` uses `30`, `AdminController::get_settings()` uses `30`, but `Plugin::http_client()` uses `8` as fallback: `get_option('yoko_lc_check_timeout', 8)`. If the option is deleted, HttpClient gets 8 seconds while the admin UI shows 30.

## Findings
**Files:** `src/Plugin.php` line 506, `src/Activator.php` line 863, `src/Admin/AdminController.php` line 1312
Found by: architecture-strategist, schema-drift-detector

## Proposed Solutions
### Option A: Align fallback in Plugin.php to 30
- **Approach:** Change `get_option('yoko_lc_check_timeout', 8)` to `get_option('yoko_lc_check_timeout', 30)` in Plugin.php.
- **Effort:** Trivial

### Option B: Create centralized Config constants
- **Approach:** Create a `Config` class with `DEFAULT_CHECK_TIMEOUT = 30` and reference it everywhere.
- **Effort:** Small

## Acceptance Criteria
- [ ] All default values for check_timeout are consistent
- [ ] HttpClient receives correct timeout when option is missing

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
