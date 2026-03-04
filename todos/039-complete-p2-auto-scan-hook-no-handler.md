---
status: complete
priority: p2
issue_id: "039"
tags: [code-review, wordpress, hooks]
dependencies: []
---

# yoko_lc_auto_scan Scheduled But No Handler Registered

## Problem Statement

`ScanOrchestrator::maybe_schedule_next_scan()` schedules recurring events on the `yoko_lc_auto_scan` hook, but no `add_action('yoko_lc_auto_scan', ...)` handler exists anywhere in the codebase. When the cron event fires, nothing happens.

## Findings

**File:** `src/Scanner/ScanOrchestrator.php` lines 509-523 — schedules `yoko_lc_auto_scan`
**File:** `src/Plugin.php` — no `add_action('yoko_lc_auto_scan', ...)` registration

Found by: wp-hooks-reviewer

## Proposed Solutions

### Option A: Add handler in Plugin::register_cron_hooks() (Recommended)
- **Approach:** Add `add_action('yoko_lc_auto_scan', array($this, 'handle_auto_scan'));` and implement `handle_auto_scan()` to call `$this->scan_orchestrator()->start_scan('full')`.
- **Effort:** Small

### Option B: Remove auto-scan scheduling until feature is ready
- **Approach:** Remove `maybe_schedule_next_scan()` call and related code until auto-scan is fully implemented.
- **Effort:** Small

## Technical Details
- **Affected files:** `src/Plugin.php`, `src/Scanner/ScanOrchestrator.php`

## Acceptance Criteria
- [ ] `yoko_lc_auto_scan` cron event has a registered handler
- [ ] Auto-scan triggers a new scan when the event fires
- [ ] Or: dead scheduling code is removed

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
