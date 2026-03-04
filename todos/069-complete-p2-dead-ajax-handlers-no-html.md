---
status: complete
priority: p2
issue_id: "069"
tags: [code-review, javascript, dead-code]
dependencies: []
---

# Dead AJAX Handlers -- No Matching HTML Elements in List Table

## Problem Statement
Three JS handlers (`handleRecheckUrl`, `handleIgnoreLink`, `handleUnignoreLink`) bind to CSS classes (`.ylc-recheck-url`, `.ylc-ignore-link`, `.ylc-unignore-link`) that are never rendered by `LinksListTable`. The list table uses standard href-based row actions instead. The recheck modal is also never triggered.

## Findings
**File:** `assets/js/admin.js` lines 47-49, 323-440
**File:** `src/Admin/LinksListTable.php` lines 194-230 (renders href actions, not AJAX buttons)
Found by: wp-javascript-reviewer

## Proposed Solutions
### Option A: Wire AJAX actions into LinksListTable
- **Approach:** Add data-* attributes and CSS classes to row actions in LinksListTable so AJAX handlers fire.
- **Effort:** Medium

### Option B: Remove dead JS handlers
- **Approach:** Remove the three handlers and their AJAX registrations. Keep the simpler href-based actions.
- **Effort:** Small

## Acceptance Criteria
- [ ] Either AJAX handlers have matching HTML or dead code is removed

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
