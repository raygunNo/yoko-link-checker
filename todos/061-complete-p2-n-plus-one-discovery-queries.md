---
status: complete
priority: p2
issue_id: "061"
tags: [code-review, performance, database]
dependencies: []
---

# N+1 Query Pattern in Discovery Phase

## Problem Statement
For every link extracted from every post, up to 4 database queries are executed: URL hash lookup, potential insert, link existence check, potential link insert. A single post with 50 links generates up to 200 queries. A batch of 50 posts averaging 20 links = 4,000 queries.

## Findings
**File:** `src/Scanner/BatchProcessor.php` lines 189-227
Found by: performance-oracle

## Proposed Solutions
### Option A: Batch URL hash lookups and inserts
- **Approach:** Use `WHERE url_hash IN (...)` for the entire post's extracted links, then multi-row INSERT for new URLs and links. Reduces 4N queries to ~4 per post.
- **Effort:** Medium

## Acceptance Criteria
- [ ] Discovery batch query count reduced from 4N to constant per post
- [ ] No duplicate URLs or links created

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
