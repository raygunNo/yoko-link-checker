---
status: complete
priority: p1
issue_id: "035"
tags: [code-review, security, ssrf]
dependencies: []
---

# SSRF Bypass in Parallel and Internal URL Request Paths

## Problem Statement

The SSRF protection added to `HttpClient::check_ssrf()` is only applied in the sequential `head()` and `get()` methods. Two other code paths bypass it entirely:

1. `UrlChecker::send_parallel_requests()` constructs requests directly for the Requests library, never calling `check_ssrf()`.
2. `BatchProcessor::check_internal_url_via_http()` calls `wp_remote_head()`/`wp_remote_get()` directly without going through `HttpClient`.

A URL pointing to `http://169.254.169.254/latest/meta-data/` (cloud metadata) would be blocked in sequential mode but allowed in parallel mode.

## Findings

**File:** `src/Checker/UrlChecker.php` lines 380-515
- `send_parallel_requests()` builds requests directly for `Requests::request_multiple()` without any SSRF validation.

**File:** `src/Scanner/BatchProcessor.php` lines 445-511
- `check_internal_url_via_http()` uses `wp_remote_head()` directly, also sets `sslverify => false`.

Found by: security-sentinel, wp-php-reviewer, architecture-strategist, wp-hooks-reviewer

## Proposed Solutions

### Option A: Filter URLs before batching (Recommended)
- **Approach:** Make `HttpClient::check_ssrf()` or `is_private_url()` public. Call it in `UrlChecker::check_batch()` before URLs enter either the parallel or sequential path. For internal URLs, add `reject_unsafe_urls => true` to the request args.
- **Pros:** Single enforcement point, covers both paths
- **Cons:** Requires making a private method public
- **Effort:** Small
- **Risk:** Low

### Option B: Add `reject_unsafe_urls` to request args
- **Approach:** Add `'reject_unsafe_urls' => true` to the parallel request options and internal URL args. This leverages WordPress core's built-in SSRF protection (`wp_http_validate_url`).
- **Pros:** Uses WordPress core protection, minimal code
- **Cons:** Only works for paths going through WP HTTP API; parallel path uses Requests library directly
- **Effort:** Small
- **Risk:** Low

## Technical Details
- **Affected files:** `src/Checker/UrlChecker.php`, `src/Scanner/BatchProcessor.php`, `src/Checker/HttpClient.php`
- **Affected components:** URL checking, SSRF protection

## Acceptance Criteria
- [ ] All URLs are SSRF-checked before HTTP requests, regardless of sequential/parallel path
- [ ] Internal URL fallback path includes SSRF protection
- [ ] `yoko_lc_allow_private_urls` filter works for all request paths
- [ ] No regression in URL checking functionality

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review of v1.0.8 refactor |
