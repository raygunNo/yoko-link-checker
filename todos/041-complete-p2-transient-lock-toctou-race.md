---
status: complete
priority: p2
issue_id: "041"
tags: [code-review, concurrency, reliability]
dependencies: []
---

# Transient Batch Lock Is Not Atomic (TOCTOU Race)

## Problem Statement

`ScanOrchestrator::process_batch()` uses `get_transient()` followed by `set_transient()` as separate operations. Two concurrent WP-Cron workers can both check the transient, find it absent, and both proceed to process the same batch.

## Findings

**File:** `src/Scanner/ScanOrchestrator.php` lines 167-178

Found by: security-sentinel, performance-oracle, wp-hooks-reviewer

## Proposed Solutions

### Option A: Use wp_cache_add() (Recommended)
- **Approach:** Replace get_transient/set_transient with `wp_cache_add()` which is atomic on persistent cache backends.
- **Effort:** Small

### Option B: Database advisory lock
- **Approach:** Use MySQL `GET_LOCK()`/`RELEASE_LOCK()` for true mutual exclusion.
- **Effort:** Medium

## Technical Details
- **Affected files:** `src/Scanner/ScanOrchestrator.php`

## Acceptance Criteria
- [ ] Batch lock acquisition is atomic
- [ ] Concurrent cron execution is properly blocked
- [ ] Lock is always released (try/finally preserved)

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
