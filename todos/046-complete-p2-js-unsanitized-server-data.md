---
status: complete
priority: p2
issue_id: "046"
tags: [code-review, javascript, security]
dependencies: []
---

# JS: Unsanitized Server Data in addClass() and css()

## Problem Statement

Two places in `admin.js` use server response data without validation:
1. `urlData.status` used directly in `addClass('ylc-status-' + urlData.status)` — could inject arbitrary CSS classes.
2. `data.progress` used directly in `css('width', data.progress + '%')` — could inject arbitrary CSS values.

## Findings

**File:** `assets/js/admin.js` line 345 — `addClass` with unvalidated status
**File:** `assets/js/admin.js` line 147 — `css('width')` with unvalidated progress

Found by: wp-javascript-reviewer

## Proposed Solutions

### Option A: Whitelist status values and coerce progress to number (Recommended)
- **Approach:** Validate `urlData.status` against known values (ok, broken, warning, etc). Coerce `data.progress` with `parseFloat()` and clamp to 0-100.
- **Effort:** Small

## Technical Details
- **Affected files:** `assets/js/admin.js`

## Acceptance Criteria
- [ ] Status value validated against whitelist before addClass()
- [ ] Progress value coerced to bounded number before css()
- [ ] Unknown values handled gracefully

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
