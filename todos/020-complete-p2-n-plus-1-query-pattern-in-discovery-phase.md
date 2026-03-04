---
status: complete
priority: p2
issue_id: "020"
tags: [code-review, performance, database]
dependencies: []
---

# N+1 Query Pattern in Discovery Phase

## Problem Statement
`BatchProcessor::process_post()` (lines 189-226) calls `find_or_create_from_raw()` per extracted link, which executes 2-4 database queries each. With 50 posts per batch and an average of 10 links per post, this results in 500 links multiplied by 3 queries each = 1,500 database queries per batch. Additionally, `ContentDiscovery::get_batch()` calls `get_post()` individually for 50 posts instead of using WordPress's `_prime_post_caches()` function. This excessive query volume causes slow batch processing and high database load.

## Findings
- `BatchProcessor::process_post()` at lines 189-226 iterates over extracted links and calls `find_or_create_from_raw()` for each one.
- `find_or_create_from_raw()` executes 2-4 queries per call: hash lookup, potential insert, potential re-fetch, link creation.
- A single batch of 50 posts with 10 links each generates approximately 1,500 database queries.
- `ContentDiscovery::get_batch()` at lines 142-148 calls `get_post()` individually for each of the 50 post IDs instead of priming the post cache.
- WordPress provides `_prime_post_caches()` specifically for batch-loading post data to avoid N+1 queries.

## Proposed Solutions

### Option A: Batch URL Hash Lookups and Prime Post Caches
- **Approach:** Collect all URL hashes from a batch and perform a single `WHERE url_hash IN (...)` query to find existing URLs. Only insert URLs that are genuinely new. Use `_prime_post_caches()` in `ContentDiscovery::get_batch()` to batch-load all 50 posts in a single query.
- **Pros:** Reduces queries from ~1,500 to ~50-100 per batch; uses existing WordPress API; significant performance improvement
- **Cons:** Requires refactoring the per-link processing loop; batch insert logic is more complex; need to handle partial failures
- **Effort:** Medium
- **Risk:** Low

### Option B: Batch INSERT with ON DUPLICATE KEY
- **Approach:** Collect all new URLs and links, then use batch INSERT statements with `ON DUPLICATE KEY UPDATE` to handle collisions. Use `_prime_post_caches()` for post data.
- **Pros:** Minimal number of queries; handles duplicates atomically; maximum performance
- **Cons:** Complex implementation; cannot use `$wpdb->insert()`; need raw SQL; harder to maintain
- **Effort:** Large
- **Risk:** Medium

### Option C: In-Memory Cache for Current Batch
- **Approach:** Maintain an in-memory hash map of URLs processed within the current batch. Check the map before querying the database. Still use `_prime_post_caches()` for posts.
- **Pros:** Simple to implement; reduces queries for URLs that appear in multiple posts within the same batch
- **Cons:** Only helps within a single batch; does not reduce queries for unique URLs; limited benefit if URLs rarely repeat within a batch
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Scanner/BatchProcessor.php` (lines 189-226)
  - `src/Scanner/ContentDiscovery.php` (lines 142-148)

## Acceptance Criteria
- [ ] URL hash lookups are batched with a single `WHERE url_hash IN (...)` query per batch
- [ ] `_prime_post_caches()` is used in `ContentDiscovery::get_batch()` to batch-load post data
- [ ] Database queries per batch are reduced from ~1,500 to under 100
- [ ] All existing functionality is preserved (URLs are still found or created correctly)
- [ ] Batch processing time is measurably reduced
- [ ] The N+1 pattern does not regress in future changes (add a comment or test)

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
