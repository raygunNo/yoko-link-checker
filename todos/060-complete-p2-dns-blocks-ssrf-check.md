---
status: complete
priority: p2
issue_id: "060"
tags: [code-review, performance, security]
dependencies: []
---

# DNS Resolution Blocks Every SSRF Check With No Caching

## Problem Statement
`gethostbyname()` in `HttpClient::check_ssrf()` is a blocking synchronous DNS lookup called for every URL before HTTP requests, including inside the parallel batch path. This serializes what should be parallel I/O. For 2,000 external URLs in batches of 5, this adds 400 blocking DNS calls.

## Findings
**File:** `src/Checker/HttpClient.php` line 399
Found by: performance-oracle

## Proposed Solutions
### Option A: Static DNS cache array
- **Approach:** Cache DNS results in a static array `self::$dns_cache` for the duration of a batch run. Same host resolved once.
- **Effort:** Small

### Option B: Use wp_safe_remote_get() which has built-in SSRF protections
- **Approach:** Leverage WordPress core's `wp_http_validate_url()` instead of custom SSRF check, potentially eliminating the custom DNS lookup.
- **Effort:** Medium

## Acceptance Criteria
- [ ] DNS lookups for the same host are not repeated within a batch
- [ ] SSRF protection still blocks private/reserved IPs

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
