---
status: pending
priority: p2
issue_id: "092"
tags: [code-review, performance, database]
dependencies: []
---

# attachment_url_to_postid() Called Without Path Guard

## Problem Statement
`attachment_url_to_postid()` performs a `LIKE '%url%'` query against `wp_postmeta` on every call. It runs for every internal URL that fails prior checks. On a site with 10,000 media attachments and 500 internal URLs, this generates 50-200ms per call.

## Findings
**File:** `src/Scanner/BatchProcessor.php` line 4823
Found by: performance-oracle

## Proposed Solutions
### Option A: Guard with media file extension check
- **Approach:** Only call `attachment_url_to_postid()` for URLs matching common media patterns (images, PDFs, videos) or `/wp-content/uploads/` paths.
- **Effort:** Small

## Acceptance Criteria
- [ ] Non-media internal URLs skip attachment lookup
- [ ] Media URLs still correctly identified

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
