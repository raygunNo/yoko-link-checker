---
status: complete
priority: p3
issue_id: "029"
tags: [code-review, performance]
dependencies: []
---

# N+1 get_post() in ContentDiscovery and LinksListTable

## Problem Statement
Multiple locations perform N+1 `get_post()` queries inside loops, issuing one database query per iteration instead of batch-loading post data. This creates unnecessary database load, particularly on sites with large link tables or during scan operations processing batches of 50 posts.

## Findings
- `ContentDiscovery::get_batch()` (lines 142-148): calls `get_post()` for each of 50 post IDs individually in a loop
- `LinksListTable::column_source()`: calls `get_post()` and `get_permalink()` per row when rendering the links admin table
- `DashboardPage::get_recent_broken()`: calls `get_post()` per row despite the underlying query already JOINing the posts table (post title data is available in `$row->post_title`)

## Proposed Solutions

### Option A: Use _prime_post_caches() and Existing JOIN Data
- **Approach:** Call `_prime_post_caches()` with the array of post IDs before entering loops in `ContentDiscovery::get_batch()` and `LinksListTable`. This pre-loads all post data in a single query, making subsequent `get_post()` calls serve from cache. For `DashboardPage::get_recent_broken()`, use `$row->post_title` directly from the JOIN result instead of calling `get_post()`.
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Scanner/ContentDiscovery.php` (lines 142-148)
  - `src/Admin/LinksListTable.php`
  - `src/Admin/DashboardPage.php`

## Acceptance Criteria
- [ ] `ContentDiscovery::get_batch()` primes post caches before the loop
- [ ] `LinksListTable::column_source()` benefits from primed caches or batched loading
- [ ] `DashboardPage::get_recent_broken()` uses JOIN data directly instead of calling `get_post()`
- [ ] Number of database queries is reduced from N+1 to 1-2 per batch
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
