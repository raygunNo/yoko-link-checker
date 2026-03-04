---
status: complete
priority: p3
issue_id: "077"
tags: [code-review, database, php]
dependencies: []
---

# get_recent_broken() Returns Duplicate URLs

## Problem Statement
If a broken URL appears in 5 different posts, the query returns 5 rows consuming 5 of the 10 LIMIT slots. The dashboard widget may show the same URL multiple times.

## Findings
**File:** `src/Repository/LinkRepository.php` lines 686-699
Found by: performance-oracle

## Proposed Solutions
### Option A: Subquery for distinct broken URLs
- **Approach:** Use a subquery to get distinct broken URLs first, then JOIN for one source per URL.
- **Effort:** Small

## Acceptance Criteria
- [ ] Dashboard shows 10 unique broken URLs

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
