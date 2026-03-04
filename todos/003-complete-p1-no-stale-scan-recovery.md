---
status: complete
priority: p1
issue_id: "003"
tags: [code-review, architecture, reliability]
dependencies: []
---

# No Stale Scan Recovery — Running Scan Blocks All Future Scans

## Problem Statement
`ScanOrchestrator::start_scan()` (line 96) checks for an existing running scan and returns `false` if one exists, preventing a new scan from starting. However, there is no mechanism to detect or recover from a scan that becomes stuck in "running" state. If a scan stalls due to a server crash, PHP timeout, killed process, or any other interruption, the stale "running" record in the database permanently blocks all future scans. The only recovery path is manual database intervention (`UPDATE yoko_lc_scans SET status='failed' WHERE status='running'`), which most site administrators would not know to do. This is a reliability-critical issue since server interruptions are not uncommon.

## Findings
- `ScanOrchestrator::start_scan()` at lines 88-102 checks for running scan and returns false if found.
- No timeout or staleness detection exists anywhere in the codebase.
- No "last activity" or "heartbeat" timestamp is tracked during scan execution.
- No admin UI provides a way to cancel or force-restart a stuck scan.
- Recovery requires direct database access.

## Proposed Solutions

### Option A: Add automatic staleness detection with configurable timeout
- **Approach:** Track a `last_activity_at` timestamp that updates on every batch process. In `start_scan()`, check if a running scan's `last_activity_at` is older than N minutes (configurable, default 30). If stale, automatically mark it as `failed` with a note, then allow the new scan to proceed. Add a cron job or check-on-load mechanism to periodically detect and clean up stale scans.
- **Pros:** Fully automatic recovery; no admin intervention needed; configurable threshold; preserves scan history with failure reason.
- **Cons:** Requires schema change to add `last_activity_at` column; needs careful threshold tuning to avoid false positives on slow scans.
- **Effort:** Medium
- **Risk:** Low

### Option B: Add "Force Start" option in the admin UI
- **Approach:** Add a "Force Start New Scan" button that appears when a scan has been running for an extended period. This button cancels the existing running scan (marks it failed) and starts a new one. Include a confirmation dialog to prevent accidental use.
- **Pros:** Gives administrators explicit control; no risk of false-positive stale detection; simpler implementation.
- **Cons:** Requires admin awareness and manual intervention; stuck scan blocks new scans until admin notices; not suitable for hands-off operation.
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Scanner/ScanOrchestrator.php` (lines 88-102)
- **Affected components:** Scan orchestrator, Scan lifecycle management, Admin UI scan controls

## Acceptance Criteria
- [ ] Stale scans (no progress in N minutes) are automatically detected and marked as failed
- [ ] New scans can start after a stale scan is recovered
- [ ] Admin UI displays a notification when a scan appears stale
- [ ] Recovery mechanism logs the stale detection for debugging
- [ ] Configurable staleness threshold (default 30 minutes)
- [ ] Normal long-running scans are not falsely flagged as stale
- [ ] Force Start option available as fallback in admin UI

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
