---
status: complete
priority: p2
issue_id: "045"
tags: [code-review, quality, duplication]
dependencies: []
---

# Duplicated Error Classification Logic

## Problem Statement

`UrlChecker::send_parallel_requests()` duplicates the `str_contains(strtolower(...))` error classification logic from `HttpClient::get_error_type()`. The same error type detection runs in two places with redundant `strtolower()` calls.

## Findings

**File:** `src/Checker/UrlChecker.php` lines 454-462 — inline error classification
**File:** `src/Checker/HttpClient.php` — `get_error_type()` has same logic

Found by: wp-php-reviewer, code-simplicity-reviewer

## Proposed Solutions

### Option A: Extract to shared method (Recommended)
- **Approach:** Make `HttpClient::classify_error_from_message(string $message): string` a public static method. Call it from both locations. Also store `strtolower()` result once.
- **Effort:** Small

## Technical Details
- **Affected files:** `src/Checker/UrlChecker.php`, `src/Checker/HttpClient.php`

## Acceptance Criteria
- [ ] Single source of truth for error type classification
- [ ] No redundant `strtolower()` calls
- [ ] Both paths produce identical results

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
