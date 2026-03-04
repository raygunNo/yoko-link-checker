---
status: complete
priority: p2
issue_id: "068"
tags: [code-review, hooks, php]
dependencies: []
---

# yoko_lc_skip_url_check Filter Leaves URL in Pending Forever

## Problem Statement
When `yoko_lc_skip_url_check` filter returns true, `check_url()` returns immediately without updating the URL status. The URL stays `pending` and is re-fetched by `get_pending()` in every future batch, creating wasted work.

## Findings
**File:** `src/Scanner/BatchProcessor.php` line 388
Found by: wp-hooks-reviewer

## Proposed Solutions
### Option A: Mark skipped URLs as 'valid' or 'skipped'
- **Approach:** When the filter says to skip, set `$url->status = Url::STATUS_VALID` and save, so it won't be re-fetched.
- **Effort:** Small

## Acceptance Criteria
- [ ] Skipped URLs are not re-fetched in subsequent batches

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
