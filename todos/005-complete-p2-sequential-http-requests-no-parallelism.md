---
status: complete
priority: p2
issue_id: "005"
tags: [code-review, performance]
dependencies: []
---

# Sequential HTTP Requests -- No Parallelism

## Problem Statement
`UrlChecker::check_batch()` (line 270) and `BatchProcessor::process_checking_batch()` (line 261) check URLs one at a time with 100ms delays between requests. With a batch size of 5 and an 8-second timeout, a single batch takes up to 40 seconds. This means checking 10K URLs takes approximately 5.5 hours, and 100K URLs takes approximately 55 hours. This makes the plugin impractical for any site with a non-trivial number of links.

## Findings
- `UrlChecker::check_batch()` at line 270 iterates over URLs sequentially, checking one at a time.
- `BatchProcessor::process_checking_batch()` at line 261 adds a 100ms delay between each request.
- No parallelism or concurrent request mechanism is used anywhere in the checking pipeline.
- Each URL check involves a full HTTP round-trip with up to 8 seconds timeout before moving to the next URL.

## Proposed Solutions

### Option A: Use WordPress Bundled Requests Library
- **Approach:** Replace sequential checks with `Requests::request_multiple()` (bundled with WordPress via the Requests library) to fire multiple HTTP requests in parallel within each batch.
- **Pros:** No additional dependencies; already bundled with WordPress; well-tested library; significant speed improvement (5x or more per batch)
- **Cons:** Need to handle per-request timeout and error handling differently; may increase server resource usage during checks
- **Effort:** Medium
- **Risk:** Medium

### Option B: Use wp_remote_get() with Async Pattern
- **Approach:** Use non-blocking `wp_remote_get()` calls with `'blocking' => false` and collect results asynchronously.
- **Pros:** Uses standard WordPress HTTP API
- **Cons:** WordPress non-blocking requests don't return response data; would need a different approach to collect results
- **Effort:** Large
- **Risk:** High

### Option C: Increase Batch Size with cURL Multi
- **Approach:** Use cURL multi handles directly for parallel requests within each batch.
- **Pros:** Maximum control over parallelism; best performance
- **Cons:** Bypasses WordPress HTTP API; requires cURL extension; more complex error handling
- **Effort:** Large
- **Risk:** Medium

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Checker/UrlChecker.php` (lines 270-281)
  - `src/Scanner/BatchProcessor.php` (line 261)

## Acceptance Criteria
- [ ] URL checking uses parallel HTTP requests instead of sequential
- [ ] Batch processing time is reduced by at least 3x for a batch of 5 URLs
- [ ] Error handling works correctly for individual request failures within a parallel batch
- [ ] Rate limiting per domain is preserved (do not fire parallel requests to the same domain)
- [ ] 100ms inter-request delay is removed or made configurable
- [ ] Existing timeout settings are respected per-request

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
