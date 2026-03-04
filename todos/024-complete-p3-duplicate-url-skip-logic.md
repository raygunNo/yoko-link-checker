---
status: complete
priority: p3
issue_id: "024"
tags: [code-review, duplication]
dependencies: []
---

# Duplicate URL-Skip Logic in 3 Places

## Problem Statement
The same empty/#/javascript:/mailto:/tel:/data: URL-skip checks are duplicated in three separate locations. This violates DRY and creates a maintenance risk: if the skip criteria change, all three locations must be updated in lockstep or behavior will diverge silently.

## Findings
- `ExtractedLink::has_processable_url()` (lines 216-250): checks for empty, `#`, `javascript:`, `mailto:`, `tel:`, `data:` URLs
- `UrlNormalizer::is_skippable()` (lines 95-127): performs the same set of checks
- `UrlRepository::find_or_create_from_raw()` (line 107): performs a subset of the same checks

## Proposed Solutions

### Option A: Consolidate to UrlNormalizer::is_skippable() as Single Source of Truth
- **Approach:** Keep `UrlNormalizer::is_skippable()` as the canonical skip-check. Simplify `ExtractedLink::has_processable_url()` to delegate to `UrlNormalizer::is_skippable()`. Remove the inline check from `UrlRepository::find_or_create_from_raw()` and call `UrlNormalizer::is_skippable()` instead.
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Extractor/ExtractedLink.php` (lines 216-250)
  - `src/Util/UrlNormalizer.php` (lines 95-127)
  - `src/Repository/UrlRepository.php` (line 107)

## Acceptance Criteria
- [ ] URL-skip logic exists in only one canonical location (`UrlNormalizer::is_skippable()`)
- [ ] `ExtractedLink::has_processable_url()` delegates to the canonical method
- [ ] `UrlRepository::find_or_create_from_raw()` delegates to the canonical method
- [ ] All previously skipped URL patterns are still correctly skipped
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
