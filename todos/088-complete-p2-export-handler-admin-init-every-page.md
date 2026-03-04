---
status: complete
priority: p2
issue_id: "088"
tags: [code-review, wordpress, performance]
dependencies: []
---

# Export Handler Fires on Every admin_init + Duplicate Call Path

## Problem Statement
`handle_early_actions()` is hooked to `admin_init` and checks `$_GET['page']` and `$_GET['action']` on every admin page load including AJAX requests. Additionally, `handle_export()` is called from both `AdminController::handle_early_actions()` and `ResultsPage::handle_actions()`, creating a fragile duplicate path.

## Findings
**File:** `src/Admin/AdminController.php` line 1084, 1096-1101
**File:** `src/Admin/ResultsPage.php` line 2125-2128
Found by: wp-hooks-reviewer, code-simplicity-reviewer

## Proposed Solutions
### Option A: Use load-{$hook_suffix} instead of admin_init
- **Approach:** Capture the hook suffix from `add_submenu_page()` return value and use `add_action("load-{$results_hook}", ...)`. Remove duplicate export call from `ResultsPage::handle_actions()`.
- **Effort:** Small

## Acceptance Criteria
- [ ] Export handler only runs on the results page
- [ ] No duplicate export call path
- [ ] Export still works correctly

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
