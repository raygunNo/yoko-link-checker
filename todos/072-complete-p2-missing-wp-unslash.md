---
status: complete
priority: p2
issue_id: "072"
tags: [code-review, php, wordpress]
dependencies: []
---

# Missing wp_unslash() Before Sanitizing $_POST Values

## Problem Statement
`handle_settings_save()` passes `$_POST` values directly to `sanitize_key()` and `absint()` without calling `wp_unslash()` first. WordPress adds slashes to all superglobal data, so values with special characters may be incorrectly processed.

## Findings
**File:** `src/Admin/AdminController.php` lines 261, 265, 271
Found by: wp-php-reviewer

## Proposed Solutions
### Option A: Add wp_unslash() before sanitization
- **Approach:** Wrap `$_POST` reads in `wp_unslash()` before passing to sanitization functions.
- **Effort:** Small

## Acceptance Criteria
- [ ] All $_POST reads use wp_unslash() before sanitization

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
