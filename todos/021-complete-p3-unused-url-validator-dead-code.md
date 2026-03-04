---
status: complete
priority: p3
issue_id: "021"
tags: [code-review, dead-code, simplification]
dependencies: []
---

# UrlValidator Is Entirely Unused Dead Code (205 LOC)

## Problem Statement
`src/Util/UrlValidator.php` (205 lines) is registered in the service container but never called from any code path. URL validation actually happens in `ExtractedLink::has_processable_url()` and `UrlNormalizer::is_skippable()` instead. This dead code adds maintenance burden, increases cognitive load when reading the codebase, and could mislead developers into thinking URL validation flows through this class.

## Findings
- `UrlValidator.php` contains 205 lines of validation logic that is never invoked at runtime.
- `Plugin.php` (lines 153-158) registers `url_validator()` in the service container, but no other code references it.
- URL validation is handled elsewhere: `ExtractedLink::has_processable_url()` and `UrlNormalizer::is_skippable()`.

## Proposed Solutions

### Option A: Delete UrlValidator and Remove Service Registration
- **Approach:** Delete `src/Util/UrlValidator.php` entirely and remove the `url_validator()` method from `Plugin.php` (lines 153-158).
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Util/UrlValidator.php` (entire file - delete)
  - `src/Plugin.php` (lines 153-158 - remove `url_validator()` method)

## Acceptance Criteria
- [ ] `src/Util/UrlValidator.php` is deleted
- [ ] `url_validator()` method is removed from `Plugin.php`
- [ ] No references to `UrlValidator` remain in the codebase
- [ ] All existing tests pass without modification
- [ ] URL validation continues to function correctly through existing code paths

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
