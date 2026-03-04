---
status: complete
priority: p3
issue_id: "075"
tags: [code-review, performance, javascript]
dependencies: []
---

# AJAX Polling at Fixed 3s With No Backoff

## Problem Statement
The scan status polling interval is a fixed 3 seconds regardless of scan phase or progress rate. No backoff on errors or stalls. Each poll loads the full WordPress admin bootstrap via admin-ajax.php.

## Findings
**File:** `assets/js/admin.js` line 16 (`const POLL_INTERVAL = 3000`)
Found by: performance-oracle

## Proposed Solutions
### Option A: Exponential backoff
- **Approach:** Start at 2s, increase to 5s after 10 polls, 10s after 30 polls. Stop on repeated errors.
- **Effort:** Small

## Acceptance Criteria
- [ ] Polling interval increases over time
- [ ] Polling stops on repeated failures

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
