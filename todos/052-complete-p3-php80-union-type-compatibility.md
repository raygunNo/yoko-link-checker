---
status: complete
priority: p3
issue_id: "052"
tags: [code-review, php, compatibility]
dependencies: []
---

# PHP 8.0 Union Type Syntax Compatibility

## Problem Statement

`UrlChecker::check_batch_parallel()` and `send_parallel_requests()` use `array|false` union return types which require PHP 8.0+. WordPress requires PHP 7.2+ (7.4+ since WP 6.7).

## Findings

**File:** `src/Checker/UrlChecker.php` lines 316, 380

Found by: wp-php-reviewer

## Proposed Solutions

### Option A: Move to docblock-only types
- **Approach:** Remove union type declarations, use `@return array|false` in docblock only.
- **Effort:** Small

## Acceptance Criteria
- [ ] No PHP 8.0+ only syntax in type declarations
- [ ] Plugin works on PHP 7.4+

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
