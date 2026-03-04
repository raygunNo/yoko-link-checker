---
status: complete
priority: p3
issue_id: "082"
tags: [code-review, dead-code, php]
dependencies: []
---

# HtmlExtractor base_url Parameter Fetched But Never Used

## Problem Statement
`HtmlExtractor::extract()` calls `get_permalink($post)` and passes `$base_url` to `extract_anchors()` and `extract_images()`, but neither method uses it for relative URL resolution.

## Findings
**File:** `src/Extractor/HtmlExtractor.php` lines 101, 166, 221
Found by: code-simplicity-reviewer, architecture-strategist

## Proposed Solutions
### Option A: Remove unused parameter
- **Approach:** Remove `$base_url` from method signatures and the `get_permalink()` call.
- **Effort:** Small

### Option B: Wire through to UrlNormalizer
- **Approach:** Actually use base_url for resolving relative URLs in content.
- **Effort:** Medium

## Acceptance Criteria
- [ ] Either parameter removed or actually used

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
