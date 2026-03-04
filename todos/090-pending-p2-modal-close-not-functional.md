---
status: pending
priority: p2
issue_id: "090"
tags: [code-review, javascript, accessibility]
dependencies: []
---

# Modal Close Button and Overlay Click Non-Functional

## Problem Statement
The recheck modal in `results.php` has a `.ylc-modal-close` span and Escape key handler, but no click handler on the close button or the overlay backdrop. The close button is visually present but non-functional.

## Findings
**File:** `templates/admin/results.php` lines 56-65
**File:** `assets/js/admin.js` lines 344-346
Found by: wp-javascript-reviewer

## Proposed Solutions
### Option A: Add click handlers
- **Approach:** Add click handlers for `.ylc-modal-close` and `.ylc-modal` overlay in `bindEvents()`.
- **Effort:** Small

### Option B: Remove dead modal entirely
- **Approach:** The modal is never opened (no trigger exists). Remove the HTML, JS, and CSS entirely.
- **Effort:** Small

## Acceptance Criteria
- [ ] Either modal is functional or removed entirely

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
