---
status: complete
priority: p2
issue_id: "065"
tags: [code-review, architecture, php]
dependencies: []
---

# Duplicate Redirect Classification in 3 Places

## Problem Statement
Redirect detection (checking if final_url differs from original URL and overriding status to REDIRECT) exists in three locations: `StatusClassifier::classify()`, `UrlChecker::process_response()`, and `UrlChecker::send_parallel_requests()`. The duplicated checks have subtly different guard conditions creating a correctness risk.

## Findings
**File:** `src/Checker/StatusClassifier.php` lines 116-118 (authoritative)
**File:** `src/Checker/UrlChecker.php` lines 186-189 (duplicate)
**File:** `src/Checker/UrlChecker.php` lines 513-515 (duplicate)
Found by: architecture-strategist, code-simplicity-reviewer

## Proposed Solutions
### Option A: Remove duplicate checks in UrlChecker
- **Approach:** Remove redirect overrides at lines 186-189 and 513-515. StatusClassifier is the single authority.
- **Effort:** Small

## Acceptance Criteria
- [ ] Redirect classification happens only in StatusClassifier
- [ ] All redirect URLs still correctly classified

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
