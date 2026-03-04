---
status: complete
priority: p3
issue_id: "081"
tags: [code-review, dead-code, javascript]
dependencies: []
---

# Orphaned yoko_lc_get_stats AJAX Endpoint

## Problem Statement
The AJAX endpoint `wp_ajax_yoko_lc_get_stats` is registered in AjaxHandler but no JavaScript ever calls it. Dashboard stats are rendered server-side in DashboardPage::render().

## Findings
**File:** `src/Admin/AjaxHandler.php` line 97 (registers handler)
**File:** `assets/js/admin.js` (no caller for this action)
Found by: call-chain-verifier

## Proposed Solutions
### Option A: Remove orphaned endpoint
- **Approach:** Remove the registration and handler method.
- **Effort:** Small

### Option B: Keep for future REST/agent use
- **Approach:** Document as internal API for future programmatic access.
- **Effort:** None

## Acceptance Criteria
- [ ] Either endpoint removed or documented as intentional

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
