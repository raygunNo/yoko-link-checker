---
status: complete
priority: p2
issue_id: "047"
tags: [code-review, javascript, quality]
dependencies: []
---

# JS: isPolling Not Reset in stopPolling + Silent Recheck Failure

## Problem Statement

Two JavaScript issues:
1. `stopPolling()` clears the interval but doesn't reset `isPolling` to `false`. If polling restarts, every `pollStatus()` call returns immediately.
2. `handleRecheckUrl` success callback silently returns on failure with no user feedback, unlike every other AJAX handler in the file.

## Findings

**File:** `assets/js/admin.js` lines 89-121 — `isPolling` never reset
**File:** `assets/js/admin.js` lines 333-338 — silent failure

Found by: wp-javascript-reviewer

## Proposed Solutions

### Option A: Fix both (Recommended)
- **Approach:** Add `isPolling = false;` to `stopPolling()`. Add `alert()` with error message to recheck failure path, matching other handlers.
- **Effort:** Small

## Technical Details
- **Affected files:** `assets/js/admin.js`

## Acceptance Criteria
- [ ] `isPolling` reset to false in `stopPolling()`
- [ ] Recheck failure shows error message to user
- [ ] `beforeunload` handler wrapped to discard return value

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
