---
status: pending
priority: p2
issue_id: "089"
tags: [code-review, wordpress, cron]
dependencies: []
---

# maybe_schedule_next_scan() Pushes First Occurrence Too Far

## Problem Statement
When a scan completes, `maybe_schedule_next_scan()` schedules the next auto-scan with `wp_schedule_event(time() + interval, ...)`. But `wp_schedule_event` already handles recurrence, so the first occurrence fires one full interval into the future, and then repeats at that interval. This is inconsistent with `handle_settings_save()` which uses `time()` as first occurrence.

## Findings
**File:** `src/Scanner/ScanOrchestrator.php` lines 5475-5489
Found by: wp-hooks-reviewer

## Proposed Solutions
### Option A: Use time() as first occurrence
- **Approach:** Change `time() + $this->get_frequency_seconds($frequency)` to `time()` to match `handle_settings_save()`.
- **Effort:** Trivial

## Acceptance Criteria
- [ ] Next auto-scan fires at the configured interval from scan completion
- [ ] Consistent with settings save scheduling

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
