---
status: complete
priority: p1
issue_id: "057"
tags: [code-review, wordpress, scanner, settings]
dependencies: []
---

# ContentDiscovery Ignores User-Configured Post Types Setting

## Problem Statement
`ContentDiscovery::get_post_types()` fetches all public post types and applies a filter, but never reads the `yoko_lc_post_types` option that the user configures in Settings. The Settings page saves to `yoko_lc_post_types`, the Activator sets a default of `array('post', 'page')`, but ContentDiscovery ignores both. A user who unchecks "Products" in settings still gets products scanned.

## Findings
**File:** `src/Scanner/ContentDiscovery.php` lines 30-42
**File:** `src/Admin/AdminController.php` line 279 (saves option)
**File:** `src/Activator.php` (sets default)
Found by: wp-php-reviewer, architecture-strategist

## Proposed Solutions

### Option A: Read option in get_post_types()
- **Approach:** Read `get_option('yoko_lc_post_types', array('post', 'page'))` as primary source, fall back to all public types only if empty. Apply `yoko_lc_scannable_post_types` filter after.
- **Effort:** Small

## Acceptance Criteria
- [ ] Changing post types in Settings affects which posts are scanned
- [ ] Default behavior (no saved option) scans post and page

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
