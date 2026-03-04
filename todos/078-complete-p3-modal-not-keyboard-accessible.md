---
status: complete
priority: p3
issue_id: "078"
tags: [code-review, accessibility, javascript]
dependencies: []
---

# Modal Not Keyboard-Accessible

## Problem Statement
The recheck modal has no Escape key handler, no focus trapping, and the close button is a `<span>` instead of a `<button>`. Not announced by screen readers.

## Findings
**File:** `assets/js/admin.js` lines 55-60
**File:** `templates/admin/results.php` line 58
Found by: wp-javascript-reviewer

## Proposed Solutions
### Option A: Add keyboard and ARIA support
- **Approach:** Add Escape key handler, change close span to button with aria-label, add aria-modal and role="dialog".
- **Effort:** Small

## Acceptance Criteria
- [ ] Modal closes with Escape key
- [ ] Close button is keyboard-focusable

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
