---
status: complete
priority: p2
issue_id: "067"
tags: [code-review, data-integrity, php]
dependencies: []
---

# redirect_count and error_type Not Set in Sequential Check Path

## Problem Statement
The parallel batch check path sets `redirect_count` and `error_type` on URL entities, but the sequential `check_url()` method does not. After a sequential check, stale values from a prior check persist in the database.

## Findings
**File:** `src/Scanner/BatchProcessor.php`
- Lines 283-291 (parallel): Sets all fields including redirect_count, error_type
- Lines 402-408 (sequential): Missing redirect_count, error_type
Found by: schema-drift-detector

## Proposed Solutions
### Option A: Add missing field assignments
- **Approach:** Add `$url->redirect_count = $result->redirect_count;` and `$url->error_type = $result->error_type;` to check_url() around line 405.
- **Effort:** Small

## Acceptance Criteria
- [ ] Sequential and parallel paths set identical URL fields

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
