---
status: complete
priority: p3
issue_id: "053"
tags: [code-review, dead-code, javascript]
dependencies: []
---

# Orphaned wp_ajax_yoko_lc_unignore_link Endpoint

## Problem Statement

The AJAX endpoint `wp_ajax_yoko_lc_unignore_link` is registered but has no JavaScript caller. Unignore only works via the non-AJAX list table URL action in `ResultsPage`.

## Findings

**File:** `src/Admin/AjaxHandler.php` line 94 — registers handler
**File:** `assets/js/admin.js` — no `handleUnignoreLink` function or `.ylc-unignore-link` binding

Found by: call-chain-verifier

## Proposed Solutions

### Option A: Add JS handler for consistency with ignore
- **Effort:** Small

### Option B: Remove unused AJAX registration
- **Effort:** Small

## Acceptance Criteria
- [ ] Either JS handler added or AJAX registration removed
- [ ] No orphaned endpoints

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
