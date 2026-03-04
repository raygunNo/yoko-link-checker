---
status: complete
priority: p2
issue_id: "014"
tags: [code-review, wordpress, hooks]
dependencies: []
---

# cron_schedules Filter Only Registered During Activation

## Problem Statement
The custom cron schedule `yoko_lc_every_minute` is registered via `add_filter('cron_schedules')` only inside `Activator::activate()` (line 39). In WordPress, the `cron_schedules` filter must be registered on every request for WP-Cron to recognize custom intervals. When WP-Cron fires, it checks the registered schedules to determine if an event is due. Since the filter is only registered during the activation request, subsequent requests do not have the custom schedule registered. This means WordPress does not recognize the `yoko_lc_every_minute` interval, potentially causing missed or misscheduled cron events.

## Findings
- `Activator::activate()` at line 39 registers the `cron_schedules` filter with `add_filter('cron_schedules', ...)`.
- This filter is only active during the plugin activation request, not on subsequent page loads.
- WordPress cron checks `wp_get_schedules()` on every request to determine which events are due.
- If a custom schedule is not registered, WordPress may skip or misschedule events using that interval.
- The cron event itself is scheduled during activation but the schedule definition is not persistent.

## Proposed Solutions

### Option A: Move cron_schedules Filter to Plugin Boot
- **Approach:** Move the `add_filter('cron_schedules', ...)` call from `Activator::activate()` to `Plugin::boot()` or the main plugin file so it runs on every request where the plugin is active.
- **Pros:** Simple fix; follows WordPress best practices; ensures schedule is always registered; minimal code change
- **Cons:** None significant
- **Effort:** Small
- **Risk:** Low

### Option B: Use Built-in WordPress Cron Intervals
- **Approach:** Instead of a custom 1-minute interval, use the built-in `hourly` schedule or Action Scheduler for more granular scheduling.
- **Pros:** No custom schedule needed; more compatible; Action Scheduler is battle-tested
- **Cons:** May require architectural changes if 1-minute granularity is needed; Action Scheduler is an additional dependency
- **Effort:** Medium
- **Risk:** Medium

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Activator.php` (line 39)

## Acceptance Criteria
- [ ] The `yoko_lc_every_minute` custom cron schedule is registered on every request (not just during activation)
- [ ] `wp_get_schedules()` includes the custom schedule on any page load when the plugin is active
- [ ] Cron events using the custom schedule fire at the expected intervals
- [ ] The schedule registration is removed from `Activator::activate()` to avoid duplication
- [ ] Plugin deactivation properly unschedules the cron events

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
