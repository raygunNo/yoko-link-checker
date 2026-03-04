---
status: complete
priority: p2
issue_id: "006"
tags: [code-review, performance, database]
dependencies: []
---

# Dashboard Fires 16+ COUNT Queries Per Load

## Problem Statement
`DashboardPage::get_stats()` (lines 87-101) and `get_status_breakdown()` (lines 109-156) fire 16+ individual COUNT queries on every dashboard page load. This is unnecessary because `UrlRepository::get_status_counts()` already exists (line 375) with a single GROUP BY query that retrieves all counts at once. Additionally, `count(get_recent(100))` fetches 100 full rows just to produce a count, and `get_recent_broken()` calls `get_post()` individually for each row instead of using the `$row->post_title` already available from the JOIN.

## Findings
- `DashboardPage::get_stats()` at lines 87-101 runs multiple individual COUNT queries for each status type.
- `DashboardPage::get_status_breakdown()` at lines 109-156 runs additional individual COUNT queries for sub-statuses.
- `UrlRepository::get_status_counts()` at line 375 already implements an efficient single GROUP BY query but is not used by the dashboard.
- `count(get_recent(100))` fetches 100 complete rows from the database only to count them.
- `get_recent_broken()` calls `get_post()` per row despite `post_title` being available from the existing JOIN query.

## Proposed Solutions

### Option A: Use Existing get_status_counts() and Fix Inefficiencies
- **Approach:** Replace the 16+ individual COUNT queries with a single call to `UrlRepository::get_status_counts()`. Replace `count(get_recent(100))` with a proper `count_all()` query. Use `$row->post_title` from the JOIN instead of calling `get_post()` in `get_recent_broken()`.
- **Pros:** Minimal code changes; uses existing infrastructure; reduces queries from 16+ to 2-3; fixes N+1 in get_recent_broken()
- **Cons:** May need to extend `get_status_counts()` if dashboard needs additional breakdowns not currently covered
- **Effort:** Small
- **Risk:** Low

### Option B: Cache Dashboard Stats with Transients
- **Approach:** Cache the dashboard stats in a transient with a short TTL (e.g., 60 seconds) so they are not recalculated on every page load.
- **Pros:** Eliminates repeated queries entirely for cached period; works regardless of query optimization
- **Cons:** Stale data for up to TTL duration; cache invalidation complexity; does not fix the underlying inefficiency
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Admin/DashboardPage.php` (lines 87-156)

## Acceptance Criteria
- [ ] Dashboard page load fires no more than 3-4 database queries for stats (down from 16+)
- [ ] `get_status_counts()` is used instead of individual COUNT queries
- [ ] `count(get_recent(100))` is replaced with a proper COUNT query
- [ ] `get_recent_broken()` uses `$row->post_title` from JOIN instead of calling `get_post()`
- [ ] Dashboard displays the same data as before the optimization
- [ ] No visual or functional regression on the dashboard page

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
