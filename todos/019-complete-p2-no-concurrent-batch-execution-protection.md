---
status: complete
priority: p2
issue_id: "019"
tags: [code-review, concurrency, data-integrity]
dependencies: []
---

# No Concurrent Batch Execution Protection

## Problem Statement
`process_batch()` can be called from both WP-Cron and AJAX status polls simultaneously. No locking mechanism exists to prevent concurrent execution. Two simultaneous batch executions can process the same URLs, create duplicate check results, or corrupt scan state. Additionally, concurrent scan starts have no atomicity -- two users clicking "Start Scan" simultaneously could both pass the `get_running()` check and create two concurrent scans.

## Findings
- `ScanOrchestrator::process_batch()` has no lock or mutex to prevent concurrent execution.
- AJAX status polling in `AjaxHandler` at line 240 can trigger batch processing while WP-Cron is also running a batch.
- `ScanOrchestrator` at lines 88-102 checks `get_running()` before starting a new scan, but this check is not atomic -- two concurrent requests can both see no running scan and both start one.
- No transient locks, database advisory locks, or filesystem locks are used anywhere in the batch processing pipeline.
- Concurrent batch execution can cause: duplicate URL checks, inconsistent scan progress counters, race conditions in URL status updates.

## Proposed Solutions

### Option A: Transient-Based Lock
- **Approach:** Use `set_transient()` with a short TTL as a lock before `process_batch()`. Check for the transient at the start and skip processing if it exists. Use `delete_transient()` when processing completes. Apply the same pattern to `start_scan()`.
- **Pros:** Simple to implement; uses existing WordPress API; no external dependencies; automatically expires if process crashes
- **Cons:** Transients stored in object cache may have race conditions; not truly atomic on some cache backends; TTL must be tuned carefully
- **Effort:** Small
- **Risk:** Medium

### Option B: Database Advisory Lock
- **Approach:** Use MySQL `GET_LOCK()` / `RELEASE_LOCK()` for a named lock around batch processing and scan start operations.
- **Pros:** Truly atomic; database-level guarantee; works across all caching configurations; well-suited for this use case
- **Cons:** MySQL-specific; lock not released if PHP process crashes (auto-releases on connection close); slightly more complex
- **Effort:** Small
- **Risk:** Low

### Option C: Row-Level Locking on Scan Record
- **Approach:** Use `SELECT ... FOR UPDATE` on the scan record to serialize access to batch processing. Only the process that acquires the row lock can proceed.
- **Pros:** Atomic; ties lock to the scan record itself; natural database pattern
- **Cons:** Requires transactions; potential for deadlocks; longer lock duration blocks other operations
- **Effort:** Medium
- **Risk:** Medium

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Scanner/ScanOrchestrator.php` (lines 88-102)
  - `src/Admin/AjaxHandler.php` (line 240)

## Acceptance Criteria
- [ ] Concurrent `process_batch()` calls are serialized -- only one executes at a time
- [ ] Concurrent `start_scan()` calls are serialized -- only one scan can be started
- [ ] Lock is automatically released if the process crashes or times out
- [ ] Lock acquisition failure results in a graceful skip (not an error)
- [ ] Scan progress counters remain accurate under concurrent access
- [ ] No duplicate URL checks occur from concurrent batch processing
- [ ] WP-Cron and AJAX batch processing do not interfere with each other

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
