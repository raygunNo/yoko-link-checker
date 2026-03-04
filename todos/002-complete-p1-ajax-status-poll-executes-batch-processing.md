---
status: complete
priority: p1
issue_id: "002"
tags: [code-review, performance, architecture, critical]
dependencies: []
---

# AJAX Status Poll Executes Full Batch Processing

## Problem Statement
The `AjaxHandler::get_scan_status()` method (lines 240-246) calls `$this->scan_orchestrator->process_batch()` on every 3-second status poll when a scan is running. During the checking phase, each batch processes up to 5 URLs with an 8-second timeout each, meaning a single status poll can block for 40+ seconds. This consumes PHP workers and makes the site unresponsive to other requests. Worse, multiple browser tabs or concurrent admin users trigger parallel batch processing with no locking mechanism, causing duplicate URL checks and race conditions on cursor updates. A read-only status endpoint should never trigger write-heavy, long-running processing.

## Findings
- `AjaxHandler::get_scan_status()` at lines 240-246 calls `$this->scan_orchestrator->process_batch()` on every poll.
- Status poll interval is 3 seconds, but `process_batch()` can block for 40+ seconds (5 URLs x 8s timeout).
- No locking mechanism prevents concurrent `process_batch()` execution from multiple polls.
- Concurrent execution causes duplicate URL checks and race conditions on scan cursor state.
- PHP workers are consumed by long-running status requests, starving other site requests.

## Proposed Solutions

### Option A: Remove process_batch() from status poll; rely on WP-Cron
- **Approach:** Make the status endpoint purely read-only — it only returns current scan state. Batch processing runs exclusively via WP-Cron. Add a `spawn_cron()` call in the status endpoint to nudge cron execution if needed, ensuring prompt batch processing without blocking the response.
- **Pros:** Clean separation of concerns; status endpoint returns instantly; no concurrency issues from polls; leverages WordPress's built-in scheduling.
- **Cons:** WP-Cron is visitor-triggered and may have delays on low-traffic sites; relies on cron reliability.
- **Effort:** Small
- **Risk:** Low

### Option B: Add separate AJAX "kick" endpoint with transient-based locking
- **Approach:** Create a dedicated AJAX endpoint (e.g., `yoko_lc_process_batch`) for triggering batch processing. Use a WordPress transient as a lock to prevent concurrent execution. The status endpoint remains read-only but can call the kick endpoint or `spawn_cron()`.
- **Pros:** Explicit control over batch processing; locking prevents concurrent execution; status endpoint stays fast.
- **Cons:** Additional endpoint to maintain; transient-based locking needs careful timeout handling; slightly more complex architecture.
- **Effort:** Medium
- **Risk:** Medium — transient locking edge cases need thorough testing.

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Admin/AjaxHandler.php` (lines 228-281)
- **Affected components:** AJAX status polling, Scan orchestration, Batch processing, WP-Cron integration

## Acceptance Criteria
- [ ] Status endpoint returns scan state without executing batch processing
- [ ] Status endpoint responds in under 1 second consistently
- [ ] Batch processing only runs via WP-Cron or a dedicated processing endpoint
- [ ] Concurrent batch execution is prevented via locking mechanism
- [ ] No duplicate URL checks occur from parallel processing
- [ ] Multiple browser tabs polling status do not degrade site performance
- [ ] Scan still progresses promptly when status page is open

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
