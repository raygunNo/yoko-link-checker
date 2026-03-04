---
status: complete
priority: p2
issue_id: "012"
tags: [code-review, security]
dependencies: []
---

# SSRF Risk -- No Private IP Range Blocking

## Problem Statement
`UrlValidator` accepts any IP address including private ranges (10.x, 172.16-31.x, 192.168.x, 127.x) and cloud metadata endpoints. A user (or malicious content) could add a link to `http://169.254.169.254/latest/meta-data/` (AWS instance metadata), `http://127.0.0.1:8080/admin`, or other internal services. `HttpClient` then makes HTTP requests to whatever URL is stored, creating a Server-Side Request Forgery (SSRF) vulnerability. On cloud-hosted WordPress sites, this could expose cloud credentials, internal service data, or allow port scanning of the internal network.

## Findings
- `UrlValidator::is_valid_host()` at lines 92-108 validates hostname format but does not check for private/reserved IP ranges.
- `HttpClient` makes requests to any URL that passes validation, with no additional IP filtering.
- No DNS resolution check is performed to catch hostnames that resolve to private IPs (DNS rebinding).
- WordPress's own HTTP API has some protections via `WP_HTTP_PROXY`, but these are not comprehensive for SSRF prevention.

## Proposed Solutions

### Option A: Add Private IP Range Check to UrlValidator
- **Approach:** Add a check in `UrlValidator::is_valid_host()` that resolves the hostname and rejects any IP in private/reserved ranges (RFC 1918, RFC 5737, loopback, link-local, cloud metadata ranges). Use `filter_var()` with `FILTER_VALIDATE_IP` and `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`.
- **Pros:** Catches private IPs at validation time; uses PHP built-in functions; prevents SSRF before any request is made
- **Cons:** DNS resolution at validation time adds latency; does not prevent DNS rebinding attacks
- **Effort:** Small
- **Risk:** Low

### Option B: Add IP Filter to HttpClient Pre-request
- **Approach:** Add a pre-request hook in `HttpClient` that resolves the target hostname and blocks requests to private IP ranges. This catches DNS rebinding since the check happens at request time.
- **Pros:** Catches DNS rebinding; defense-in-depth; closer to the actual HTTP request
- **Cons:** Requires DNS resolution before every request; more complex implementation
- **Effort:** Medium
- **Risk:** Low

### Option C: Both Validation and Pre-request Filtering
- **Approach:** Combine Options A and B for defense-in-depth. Reject obvious private IPs at validation time, and also check resolved IPs at request time.
- **Pros:** Maximum protection; catches both direct IP input and DNS rebinding
- **Cons:** Two DNS lookups (one at validation, one at request); slightly more complex
- **Effort:** Medium
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Util/UrlValidator.php` (lines 92-108)
  - `src/Checker/HttpClient.php`

## Acceptance Criteria
- [ ] URLs with private IP addresses (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16) are rejected
- [ ] Loopback addresses (127.0.0.0/8, ::1) are rejected
- [ ] Link-local addresses (169.254.0.0/16) including cloud metadata endpoints are rejected
- [ ] Reserved IP ranges are rejected
- [ ] Hostnames that resolve to private IPs are blocked
- [ ] Legitimate external URLs continue to work normally
- [ ] A filter/hook is available to allow site administrators to whitelist specific internal URLs if needed
- [ ] Blocked URLs are logged with the reason for blocking

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
