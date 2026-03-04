---
status: complete
priority: p3
issue_id: "084"
tags: [code-review, javascript, quality]
dependencies: []
---

# var Used Instead of const/let in admin.js

## Problem Statement
Several locations use `var` instead of `const`/`let`, inconsistent with the rest of the file and WordPress JavaScript coding standards.

## Findings
**File:** `assets/js/admin.js` lines 151, 353-362, 418-419
Found by: wp-javascript-reviewer

## Proposed Solutions
### Option A: Replace var with const/let
- **Approach:** Use `const` where value is not reassigned, `let` where it is.
- **Effort:** Small

## Acceptance Criteria
- [ ] No var declarations remain in admin.js

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
