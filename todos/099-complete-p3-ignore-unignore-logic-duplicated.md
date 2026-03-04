---
status: complete
priority: p3
issue_id: "099"
tags: [code-review, architecture, php]
dependencies: []
---

# Ignore/Unignore Business Logic Duplicated

## Problem Statement
The ignore/unignore workflow is implemented in both `AjaxHandler` and `ResultsPage` with identical business logic (find link, mark URL, fire hook). Only the response format differs (JSON vs redirect).

## Findings
**File:** `src/Admin/AjaxHandler.php` lines 1623-1693
**File:** `src/Admin/ResultsPage.php` lines 2213-2265
Found by: architecture-strategist, code-simplicity-reviewer

## Proposed Solutions
### Option A: Extract shared service method
- **Approach:** Create a shared method (e.g., on UrlRepository or new LinkService) that handles the business logic. Both AjaxHandler and ResultsPage call it and handle only response formatting.
- **Effort:** Small

## Acceptance Criteria
- [ ] Ignore/unignore business logic in single location
- [ ] Both AJAX and list table paths use shared method

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
