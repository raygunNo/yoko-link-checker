---
status: complete
priority: p2
issue_id: "064"
tags: [code-review, security, php, wordpress]
dependencies: []
---

# Missing wpdb->prepare() for LIKE Patterns

## Problem Statement
Three DELETE queries use LIKE patterns with string interpolation instead of `$wpdb->prepare()` with `$wpdb->esc_like()`. While the patterns are static strings (no injection vector), this violates WordPress coding standards and is a maintenance hazard.

## Findings
**File:** `src/Deactivator.php` lines 104-106
**File:** `uninstall.php` lines 61-63, 97-99
Found by: wp-php-reviewer

## Proposed Solutions
### Option A: Use $wpdb->prepare() with esc_like()
- **Approach:** Wrap all LIKE queries in `$wpdb->prepare()` using `$wpdb->esc_like()` for the prefix pattern.
- **Effort:** Small

## Acceptance Criteria
- [ ] All LIKE queries use $wpdb->prepare()
- [ ] PHPCS warnings resolved for these queries

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
