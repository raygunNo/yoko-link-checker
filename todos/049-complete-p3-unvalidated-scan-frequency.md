---
status: complete
priority: p3
issue_id: "049"
tags: [code-review, security, validation]
dependencies: []
---

# Unvalidated scan_frequency Option Value

## Problem Statement

`AdminController` uses `sanitize_key()` for `yoko_lc_auto_scan_frequency` but doesn't validate against a whitelist of allowed cron schedule names.

## Findings

**File:** `src/Admin/AdminController.php` line 271

Found by: security-sentinel

## Proposed Solutions

### Option A: Add whitelist validation
- **Approach:** Validate against `array('hourly', 'twicedaily', 'daily', 'weekly')` before saving.
- **Effort:** Small

## Acceptance Criteria
- [ ] Only valid cron schedule names accepted

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
