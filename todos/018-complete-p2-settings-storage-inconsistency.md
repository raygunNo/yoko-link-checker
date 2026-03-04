---
status: complete
priority: p2
issue_id: "018"
tags: [code-review, architecture]
dependencies: []
---

# Settings Storage Inconsistency

## Problem Statement
`Activator` creates a grouped `yoko_lc_settings` array (line 165) containing rate limits, recheck intervals, and other settings for features that do not exist in the plugin. Meanwhile, `AdminController` writes and reads entirely different individual options (e.g., `yoko_lc_timeout`, `yoko_lc_batch_size`). The grouped `yoko_lc_settings` option is dead code that is never read by any functional part of the plugin. Several settings within it (`rate_limit_per_domain`, `recheck_valid_days`, `scan_on_publish`) have no implementation anywhere. This creates confusion for developers and wastes a database row.

## Findings
- `Activator` at lines 165-198 creates `yoko_lc_settings` as a serialized array with multiple keys.
- `AdminController` at lines 270-291 reads and writes individual options like `yoko_lc_timeout`, `yoko_lc_batch_size`, etc.
- No code in the plugin reads from the `yoko_lc_settings` grouped option.
- Settings in the grouped option that have no implementation: `rate_limit_per_domain`, `recheck_valid_days`, `scan_on_publish`, `email_notifications`, `notification_email`.
- The two storage patterns are completely disconnected -- changing settings via the admin UI does not affect the grouped option and vice versa.

## Proposed Solutions

### Option A: Remove Grouped yoko_lc_settings from Activator
- **Approach:** Remove the `yoko_lc_settings` option creation from `Activator`. Keep the individual options pattern used by `AdminController` as the canonical approach. Add `yoko_lc_settings` to the uninstall cleanup. Remove any references to unimplemented settings.
- **Pros:** Eliminates dead code; reduces confusion; aligns activation with actual behavior; simplest fix
- **Cons:** If anyone is reading `yoko_lc_settings` via custom code, it would break (unlikely)
- **Effort:** Small
- **Risk:** Low

### Option B: Migrate to Grouped Settings Pattern
- **Approach:** Refactor `AdminController` to use the grouped `yoko_lc_settings` array for all settings. Remove individual options. Create a `Settings` class to manage read/write.
- **Pros:** Single source of truth; fewer database rows; cleaner architecture; consistent pattern
- **Cons:** Larger refactoring effort; migration needed for existing installs; all settings access points need updating
- **Effort:** Large
- **Risk:** Medium

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Activator.php` (lines 165-198)
  - `src/Admin/AdminController.php` (lines 270-291)

## Acceptance Criteria
- [ ] Only one settings storage pattern is used throughout the plugin
- [ ] No dead-code settings options are created during activation
- [ ] Settings that have no implementation are removed (or documented as planned features)
- [ ] Existing user settings are preserved during any migration
- [ ] `uninstall.php` is updated to clean up the correct options (see issue #011)

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
