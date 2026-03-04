---
status: complete
priority: p1
issue_id: "085"
tags: [code-review, javascript, bug]
dependencies: []
---

# Polling Race Condition Can Permanently Stall Scan Status Updates

## Problem Statement
In `scheduleNextPoll()`, the next poll is scheduled synchronously inside the `setTimeout` callback *before* the AJAX call from `pollStatus()` completes. Because the AJAX call is asynchronous, `scheduleNextPoll()` fires a new timer while the HTTP request is still in flight. If the AJAX call takes longer than the poll interval, the `isPolling` guard prevents the next poll from executing, `pollIntervalId` is already set to `null`, and `scheduleNextPoll` is never called again. Polling permanently stops.

## Findings
**File:** `assets/js/admin.js` lines 103-110
Found by: wp-javascript-reviewer, performance-oracle

The current code:
```javascript
function scheduleNextPoll() {
    pollIntervalId = setTimeout(function() {
        pollCount++;
        pollStatus();
        pollIntervalId = null;
        scheduleNextPoll();  // Schedules next before AJAX completes
    }, getPollInterval());
}
```

## Proposed Solutions
### Option A: Schedule next poll in AJAX complete callback
- **Approach:** Move `scheduleNextPoll()` into the AJAX `complete` callback of `pollStatus()` so the interval is measured from when the response arrives. Pass a callback parameter to `pollStatus()`. Remove the `isPolling` guard since it becomes unnecessary.
- **Effort:** Small

## Acceptance Criteria
- [ ] Next poll is only scheduled after current AJAX response arrives
- [ ] Polling never permanently stalls under slow network conditions
- [ ] `isPolling` guard removed (no longer needed)
- [ ] Scan status updates continue reliably throughout long scans

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
