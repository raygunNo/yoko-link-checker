---
status: complete
priority: p3
issue_id: "026"
tags: [code-review, simplification]
dependencies: []
---

# Count Method Aliases in UrlRepository

## Problem Statement
`UrlRepository` contains several count method aliases -- `count_all()`, `count_by_status()`, `count_pending()` -- that all delegate to a single `count()` method. There are only 3 call sites total, and `count_pending()` is never called at all. These trivial wrappers add unnecessary API surface without providing meaningful abstraction.

## Findings
- `count_all()`, `count_by_status()`, `count_pending()` all delegate to `count()` (lines 329-475)
- Only 3 call sites use these methods across the codebase
- `count_pending()` has zero call sites (dead code)

## Proposed Solutions

### Option A: Remove Aliases and Use count() Directly
- **Approach:** Delete `count_all()`, `count_by_status()`, and `count_pending()` from `UrlRepository`. Update the 3 call sites to use `count()` directly with appropriate parameters.
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Repository/UrlRepository.php` (lines 329-475)

## Acceptance Criteria
- [ ] `count_all()`, `count_by_status()`, and `count_pending()` are removed from `UrlRepository`
- [ ] All 3 call sites are updated to use `count()` directly
- [ ] No references to removed methods remain in the codebase
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
