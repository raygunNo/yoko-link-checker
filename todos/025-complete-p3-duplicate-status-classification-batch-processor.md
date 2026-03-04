---
status: complete
priority: p3
issue_id: "025"
tags: [code-review, duplication, architecture]
dependencies: []
---

# Duplicate Status Classification in BatchProcessor

## Problem Statement
`BatchProcessor::check_internal_url_via_http()` (lines 502-530) contains its own inline HTTP status classification logic that diverges from the centralized `StatusClassifier`. It treats 401/403 differently than `StatusClassifier` does. Additionally, `UrlChecker` has a duplicate redirect check (lines 187-189) after `StatusClassifier` already handles redirect classification. This duplication risks inconsistent status reporting across code paths.

## Findings
- `BatchProcessor::check_internal_url_via_http()` (lines 451-533) has inline status classification for HTTP responses, treating 401/403 differently than `StatusClassifier`
- `UrlChecker` (lines 186-189) contains a redundant redirect check that duplicates logic already in `StatusClassifier`
- `StatusClassifier` is the intended centralized location for all HTTP status classification

## Proposed Solutions

### Option A: Use StatusClassifier Consistently
- **Approach:** Replace the inline status classification in `BatchProcessor::check_internal_url_via_http()` with a call to `StatusClassifier::classify()`. Remove the duplicate redirect check from `UrlChecker` (lines 186-189). If the 401/403 handling difference is intentional for internal URLs, add a parameter or method to `StatusClassifier` to support that variation.
- **Effort:** Medium

## Technical Details
- **Affected files:**
  - `src/Scanner/BatchProcessor.php` (lines 451-533)
  - `src/Checker/UrlChecker.php` (lines 186-189)

## Acceptance Criteria
- [ ] `BatchProcessor::check_internal_url_via_http()` uses `StatusClassifier::classify()` instead of inline classification
- [ ] Duplicate redirect check is removed from `UrlChecker`
- [ ] Any intentional differences in 401/403 handling are preserved via `StatusClassifier` configuration
- [ ] Status classification behavior remains consistent across all code paths
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
