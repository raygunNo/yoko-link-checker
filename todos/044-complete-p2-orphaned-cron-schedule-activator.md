---
status: complete
priority: p2
issue_id: "044"
tags: [code-review, wordpress, dead-code]
dependencies: []
---

# Orphaned yoko_lc_five_minutes Cron Schedule in Activator

## Problem Statement

`Activator::schedule_cron_events()` registers a `yoko_lc_five_minutes` schedule via an anonymous closure, but no code uses this schedule. Meanwhile, `Plugin::add_cron_schedules()` registers a different `yoko_lc_every_minute` schedule. Two unused schedules in two different places, neither actually used by any `wp_schedule_event()` call.

## Findings

**File:** `src/Activator.php` lines 192-201 — closure registers `yoko_lc_five_minutes`
**File:** `src/Plugin.php` lines 135-143 — registers `yoko_lc_every_minute`

Found by: wp-hooks-reviewer, wp-php-reviewer, code-simplicity-reviewer

## Proposed Solutions

### Option A: Remove Activator closure, keep Plugin registration (Recommended)
- **Approach:** Delete the `add_filter('cron_schedules', ...)` closure from Activator. If the `yoko_lc_every_minute` schedule is also unused, remove it from Plugin too.
- **Effort:** Small

## Technical Details
- **Affected files:** `src/Activator.php`, potentially `src/Plugin.php`

## Acceptance Criteria
- [ ] No orphaned cron schedule registrations
- [ ] Closure-based schedule removed from Activator
- [ ] Any remaining schedule registrations are actually used

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
