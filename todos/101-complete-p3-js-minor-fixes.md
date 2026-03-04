---
status: complete
priority: p3
issue_id: "101"
tags: [code-review, javascript, quality]
dependencies: []
---

# JavaScript Minor Fixes Bundle

## Problem Statement
Several small JS/PHP quality issues:
1. No guard against missing `ylcAdmin` global in admin.js
2. Deprecated `e.keyCode` fallback (e.key is sufficient)
3. Non-Yoda conditions in autoloader and is_plugin_page()
4. Phantom `yoko_lc_settings` option in uninstall.php (never created)
5. get_edit_post_link() null not handled in LinksListTable
6. sanitize_csv_value() doesn't type-cast non-string input

## Findings
**Files:** `assets/js/admin.js` line 51, `yoko-link-checker.php` line 65, `src/Admin/AdminController.php` line 1200, `uninstall.php`, `src/Admin/LinksListTable.php` line 2749, `src/Admin/ResultsPage.php` line 2365
Found by: wp-javascript-reviewer, wp-php-reviewer, schema-drift-detector

## Proposed Solutions
### Option A: Fix all in one pass
- **Approach:** Add ylcAdmin guard, remove keyCode fallback, fix Yoda conditions, remove phantom option, add null check on get_edit_post_link, add type cast in sanitize_csv_value.
- **Effort:** Small

## Acceptance Criteria
- [ ] All minor quality issues addressed
- [ ] No functional regressions

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
