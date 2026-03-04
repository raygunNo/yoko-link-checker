---
status: complete
priority: p1
issue_id: "059"
tags: [code-review, settings, php]
dependencies: []
---

# check_timeout Setting Is Non-Functional (Never Consumed by HttpClient)

## Problem Statement
The Settings page allows configuring `yoko_lc_check_timeout` (clamped to 5-120 seconds), and the value is saved to `wp_options` and displayed correctly. However, `Plugin::http_client()` creates `new HttpClient()` with no arguments, so the timeout always defaults to 8 seconds. The user-configured timeout has zero effect on actual HTTP requests.

## Findings
**File:** `src/Admin/AdminController.php` line 264-266 (saves option)
**File:** `src/Plugin.php` line 256-259 (creates HttpClient with defaults)
**File:** `src/Checker/HttpClient.php` line 70 (hardcoded default 8)
Found by: wp-php-reviewer, call-chain-verifier, security-sentinel

## Proposed Solutions

### Option A: Pass saved option to HttpClient constructor
- **Approach:** Change `fn() => new HttpClient()` to `fn() => new HttpClient((int) get_option('yoko_lc_check_timeout', 8))` in Plugin.php.
- **Effort:** Small (one-line fix)

## Acceptance Criteria
- [ ] Changing timeout in Settings affects actual HTTP request timeouts

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
