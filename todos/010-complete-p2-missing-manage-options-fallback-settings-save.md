---
status: complete
priority: p2
issue_id: "010"
tags: [code-review, authorization, php]
dependencies: []
---

# Missing manage_options Fallback in Settings Save

## Problem Statement
`AdminController::handle_settings_save()` (line 250) checks only the custom `yoko_lc_manage_settings` capability with no `manage_options` fallback. However, menu registration (line 104) DOES include a `manage_options` fallback, meaning an admin can see and access the settings page but cannot save settings if the custom capability has not been provisioned. This creates a confusing user experience where the settings form is visible and editable but silently fails (or returns a permission error) on save. The same inconsistency exists in `ResultsPage` at lines 98 and 218.

## Findings
- `AdminController::handle_settings_save()` at line 250 only checks `current_user_can('yoko_lc_manage_settings')`.
- Menu registration at line 104 checks `yoko_lc_manage_settings` OR `manage_options` as fallback.
- `AjaxHandler::verify_request()` correctly uses the fallback pattern with `manage_options`.
- `ResultsPage` at lines 98 and 218 also lacks the `manage_options` fallback.
- On a fresh install or when custom capabilities are not provisioned, administrators can view but not save settings.

## Proposed Solutions

### Option A: Add manage_options Fallback to All Capability Checks
- **Approach:** Add `&& ! current_user_can('manage_options')` to the capability checks in `handle_settings_save()` and `ResultsPage`, matching the pattern already established in `AjaxHandler::verify_request()`.
- **Pros:** Consistent behavior across all capability checks; matches existing pattern in the codebase; simple change
- **Cons:** None significant
- **Effort:** Small
- **Risk:** Low

### Option B: Create a Centralized Capability Check Method
- **Approach:** Create a helper method like `UserCapabilities::can($capability)` that always includes the `manage_options` fallback, and use it everywhere.
- **Pros:** Single source of truth; prevents future inconsistencies; easier to modify fallback logic
- **Cons:** Slightly more refactoring; introduces new class/method
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Admin/AdminController.php` (line 250)
  - `src/Admin/ResultsPage.php` (lines 98, 218)

## Acceptance Criteria
- [ ] `handle_settings_save()` allows users with `manage_options` capability even if `yoko_lc_manage_settings` is not provisioned
- [ ] `ResultsPage` capability checks include `manage_options` fallback
- [ ] All capability checks in the plugin follow a consistent pattern
- [ ] An administrator on a fresh install can both view and save settings
- [ ] Custom capability still works when provisioned (does not break existing setups)

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
