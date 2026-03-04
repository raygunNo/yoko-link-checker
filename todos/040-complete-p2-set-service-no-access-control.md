---
status: complete
priority: p2
issue_id: "040"
tags: [code-review, security, architecture]
dependencies: []
---

# set_service() Has No Access Control Guard

## Problem Statement

`Plugin::set_service()` is public with no guards. While documented for tests, it allows any code with access to the Plugin instance (available via `yoko_lc_booted` action) to replace any service at runtime, including the HTTP client or scan orchestrator.

## Findings

**File:** `src/Plugin.php` lines 406-408

Found by: security-sentinel

## Proposed Solutions

### Option A: Guard with booted flag (Recommended)
- **Approach:** Add `if ($this->booted && !defined('YOKO_LC_TESTING')) { return; }` to prevent post-boot replacement.
- **Effort:** Small

## Technical Details
- **Affected files:** `src/Plugin.php`

## Acceptance Criteria
- [ ] `set_service()` is blocked after plugin boot in production
- [ ] `set_service()` works when `YOKO_LC_TESTING` is defined
- [ ] Existing test patterns continue to work

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
