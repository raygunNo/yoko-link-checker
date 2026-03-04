---
status: complete
priority: p1
issue_id: "037"
tags: [code-review, performance, dead-code]
dependencies: ["035"]
---

# Parallel check_batch() Never Wired Into Scan Pipeline

## Problem Statement

`UrlChecker::check_batch()` implements parallel HTTP requests via `Requests::request_multiple()`, but `BatchProcessor::process_checking_batch()` still iterates URLs one at a time calling `$this->check_url()` sequentially. The entire parallel HTTP implementation (~200 lines) is dead code in the actual scan flow. The 5x-10x throughput improvement from parallel requests is never realized.

## Findings

**File:** `src/Scanner/BatchProcessor.php` lines 261-264 — still sequential:
```php
foreach ( $urls as $url ) {
    try {
        $this->check_url( $url );
```

**File:** `src/Checker/UrlChecker.php` lines 280-515 — `check_batch()`, `check_batch_parallel()`, `send_parallel_requests()` never called by the scan pipeline.

Found by: performance-oracle

## Proposed Solutions

### Option A: Wire check_batch() into process_checking_batch() (Recommended)
- **Approach:** Separate external URLs from internal URLs in `process_checking_batch()`. Send external URLs through `check_batch()` for parallel processing. Keep internal URLs sequential (they use WordPress functions, not HTTP).
- **Pros:** Realizes the 5x-10x throughput improvement; code already written and tested
- **Cons:** Requires mapping parallel results back to Url model objects
- **Effort:** Medium
- **Risk:** Medium (depends on #035 SSRF fix being applied first)

## Technical Details
- **Affected files:** `src/Scanner/BatchProcessor.php`, `src/Checker/UrlChecker.php`

## Acceptance Criteria
- [ ] External URLs are checked in parallel via `check_batch()`
- [ ] Internal URLs continue to use `check_internal_url_via_http()`
- [ ] Parallel results are correctly mapped back to Url model objects
- [ ] SSRF protection is applied to parallel requests (depends on #035)
- [ ] Fallback to sequential on parallel failure still works

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
