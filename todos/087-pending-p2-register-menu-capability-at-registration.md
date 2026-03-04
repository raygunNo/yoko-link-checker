---
status: pending
priority: p2
issue_id: "087"
tags: [code-review, wordpress, security]
dependencies: []
---

# register_menu() Evaluates Capability at Registration Time

## Problem Statement
`AdminController::register_menu()` calls `current_user_can()` at `admin_menu` time to determine which capability string to pass to `add_menu_page()`. This bakes in the capability based on the first admin user who loads the page. If custom capabilities haven't been assigned yet, all menus fall back to `manage_options`, locking out editors who have custom caps but not `manage_options`.

## Findings
**File:** `src/Admin/AdminController.php` lines 1112-1113
Found by: wp-hooks-reviewer, security-sentinel, architecture-strategist

## Proposed Solutions
### Option A: Always register with custom capability
- **Approach:** Use fixed strings `'yoko_lc_view_results'` and `'yoko_lc_manage_settings'` directly. Rely on `Activator::set_capabilities()` to grant them to admin role.
- **Effort:** Small

## Acceptance Criteria
- [ ] Menu pages always registered with custom capability string
- [ ] Editors with custom caps can see menu items
- [ ] Activation hook properly grants capabilities

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
