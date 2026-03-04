---
status: complete
priority: p3
issue_id: "032"
tags: [code-review, architecture]
dependencies: []
---

# DashboardPage Bypasses Repository Abstraction

## Problem Statement
`DashboardPage::get_recent_broken()` (lines 182-201) writes raw SQL using `global $wpdb` and joins tables directly, completely bypassing both repository classes. This violates the repository pattern established elsewhere in the codebase, scatters SQL across the presentation layer, and makes the query harder to test and maintain.

## Findings
- `DashboardPage::get_recent_broken()` (lines 182-201) uses `global $wpdb` directly
- The method performs a JOIN across link and URL tables with raw SQL
- Both `LinkRepository` and `UrlRepository` exist and are the intended abstraction for database access
- No other presentation-layer code writes raw SQL

## Proposed Solutions

### Option A: Move Query into LinkRepository
- **Approach:** Create a dedicated `LinkRepository::get_recent_broken(int $limit)` method that encapsulates the JOIN query. Update `DashboardPage::get_recent_broken()` to delegate to the repository method. This keeps all SQL in the repository layer where it belongs.
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Admin/DashboardPage.php` (lines 182-201)
  - `src/Repository/LinkRepository.php` (new method)

## Acceptance Criteria
- [ ] Raw SQL is moved from `DashboardPage` into `LinkRepository::get_recent_broken()`
- [ ] `DashboardPage` calls the repository method instead of using `global $wpdb`
- [ ] Query results and behavior are identical to the current implementation
- [ ] No `global $wpdb` usage remains in `DashboardPage`
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
