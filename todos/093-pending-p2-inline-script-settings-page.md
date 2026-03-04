---
status: pending
priority: p2
issue_id: "093"
tags: [code-review, javascript, wordpress]
dependencies: []
---

# Inline Script in Settings Template Bypasses Asset Pipeline

## Problem Statement
The settings page has an inline `<script>` block for toggling auto-scan options. This bypasses WordPress script registration, cannot be cached, and fails under strict CSP headers.

## Findings
**File:** `templates/admin/settings.php` lines 144-151
Found by: wp-javascript-reviewer

## Proposed Solutions
### Option A: Move to admin.js bindEvents()
- **Approach:** Move the toggle logic into `admin.js` within `bindEvents()` and remove the inline script.
- **Effort:** Small

## Acceptance Criteria
- [ ] No inline scripts in templates
- [ ] Auto-scan toggle still works on settings page

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
