---
status: complete
priority: p2
issue_id: "017"
tags: [code-review, performance]
dependencies: []
---

# CSV Export Loads Entire Dataset Into Memory

## Problem Statement
`LinkRepository::get_all_for_export()` loads ALL rows into memory with no LIMIT clause, then calls `get_permalink()` for each row (N+1 query pattern). At 100K links (approximately 50MB of data), this exceeds typical PHP memory limits. At 1M links (approximately 500MB), it causes a guaranteed out-of-memory (OOM) fatal error. This makes CSV export unusable for sites with a large number of tracked links.

## Findings
- `LinkRepository::get_all_for_export()` at lines 598-634 executes a query with no LIMIT clause, loading all rows into a PHP array.
- Each row triggers a `get_permalink()` call, resulting in N+1 queries (one additional query per link).
- PHP's default memory limit is 128MB or 256MB; a large dataset easily exceeds this.
- No streaming or chunking mechanism is used for the export.
- The entire dataset must fit in memory before any CSV output begins.

## Proposed Solutions

### Option A: Stream CSV with Chunked Queries and Generator Pattern
- **Approach:** Replace the bulk query with chunked queries (e.g., 1000 rows at a time) using a PHP generator. Stream CSV output directly to `php://output` with appropriate headers. Use `_prime_post_caches()` per chunk to batch-load post data instead of calling `get_permalink()` individually.
- **Pros:** Constant memory usage regardless of dataset size; works for any number of links; faster due to batched post cache priming
- **Cons:** Slightly more complex implementation; cannot easily add headers/footers to CSV; harder to test
- **Effort:** Medium
- **Risk:** Low

### Option B: Background Export with File Download
- **Approach:** Generate the CSV file in the background (via cron or async task), save it to a temporary file, and provide a download link when ready.
- **Pros:** No memory issues; no request timeout issues; user can continue working while export generates
- **Cons:** More complex UX; requires temporary file management; cleanup of old exports; more code
- **Effort:** Large
- **Risk:** Medium

### Option C: Add LIMIT with Pagination
- **Approach:** Add pagination to the export with a configurable limit (e.g., 10K rows per export file). User downloads multiple files for large datasets.
- **Pros:** Simple implementation; predictable memory usage
- **Cons:** Poor UX for large datasets; user must download multiple files; no single complete export
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Repository/LinkRepository.php` (lines 598-634)

## Acceptance Criteria
- [ ] CSV export works for datasets of 100K+ links without exceeding PHP memory limits
- [ ] Export uses constant (or near-constant) memory regardless of dataset size
- [ ] N+1 `get_permalink()` calls are replaced with batched post cache priming
- [ ] CSV output is streamed to the browser progressively
- [ ] Export does not time out for large datasets (or handles timeout gracefully)
- [ ] Exported CSV format and content remain identical to current output

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
