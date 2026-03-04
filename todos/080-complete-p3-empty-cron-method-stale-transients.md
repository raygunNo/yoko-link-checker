---
status: complete
priority: p3
issue_id: "080"
tags: [code-review, dead-code, php]
dependencies: []
---

# Empty schedule_cron_events() Method and Stale Transients

## Problem Statement
`Activator::schedule_cron_events()` is empty (just a comment) but still called from `activate()`. Also, `yoko_lc_flush_rewrite` transient is set in Activator but never checked, and `yoko_lc_rate_limit_state` is deleted in Deactivator but never set.

## Findings
**File:** `src/Activator.php` lines 48, 52, 189-193
**File:** `src/Deactivator.php` lines 97, 99
Found by: code-simplicity-reviewer

## Proposed Solutions
### Option A: Remove dead code
- **Approach:** Remove empty method, its call, the stale transient set/delete.
- **Effort:** Small

## Acceptance Criteria
- [ ] No empty methods called
- [ ] No stale transient references

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
