---
status: complete
priority: p2
issue_id: "016"
tags: [code-review, javascript, memory-leak]
dependencies: []
---

# Polling Timer Never Cleared on Page Unload

## Problem Statement
`admin.js` uses `setInterval` for 3-second polling during scans but never calls `clearInterval` on page unload or navigation. Orphaned intervals continue making AJAX requests to the server even after the user navigates away from the page. This causes unnecessary server load, potential race conditions with stale requests, and memory leaks in the browser tab.

## Findings
- `admin.js` creates a `setInterval` with a 3-second interval for polling scan status.
- No `clearInterval` call exists on `beforeunload`, `unload`, or `pagehide` events.
- No `clearInterval` call exists when the scan completes, fails, or is cancelled.
- Orphaned intervals continue firing AJAX requests to the server indefinitely.
- In single-page admin navigation (e.g., Turbo-style or AJAX-based page transitions), intervals persist across page changes.

## Proposed Solutions

### Option A: Store Interval ID and Clear on Events
- **Approach:** Store the interval ID returned by `setInterval` in a variable. Add a `beforeunload` event listener that calls `clearInterval`. Also clear the interval when the scan completes, fails, or is cancelled.
- **Pros:** Simple and direct; minimal code change; addresses all scenarios
- **Cons:** None significant
- **Effort:** Small
- **Risk:** Low

### Option B: Replace setInterval with setTimeout Chain
- **Approach:** Replace the `setInterval` pattern with recursive `setTimeout` calls. Each poll schedules the next one only after the previous AJAX request completes. This naturally prevents orphaned intervals and avoids request pileup if the server is slow.
- **Pros:** Self-correcting; no orphaned intervals possible; prevents request pileup; better error handling
- **Cons:** Slightly more complex code; polling interval includes request time
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `assets/js/admin.js`

## Acceptance Criteria
- [ ] Polling interval is cleared when the user navigates away from the page
- [ ] Polling interval is cleared when the scan completes successfully
- [ ] Polling interval is cleared when the scan fails or is cancelled
- [ ] No orphaned AJAX requests continue after page navigation
- [ ] No memory leaks from orphaned intervals
- [ ] Polling correctly resumes if the user returns to the scan page during an active scan

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
