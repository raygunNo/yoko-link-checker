---
status: complete
priority: p3
issue_id: "048"
tags: [code-review, quality, php]
dependencies: []
---

# ResultsPage ignore/unignore Missing Return Value Checks

## Problem Statement

`ResultsPage::ignore_link()` and `unignore_link()` don't check the return value of `mark_ignored()`/`unmark_ignored()`, unlike the AJAX handlers which properly check. Users are silently redirected regardless of success/failure.

## Findings

**File:** `src/Admin/ResultsPage.php` lines 178-219
**File:** `src/Admin/AjaxHandler.php` line 318 — correctly checks return

Found by: call-chain-verifier

## Proposed Solutions

### Option A: Add return value checks and error query param
- **Effort:** Small

## Acceptance Criteria
- [ ] Return values checked in both methods
- [ ] Error state communicated to user after redirect

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
