---
status: complete
priority: p2
issue_id: "011"
tags: [code-review, data-integrity, wordpress]
dependencies: []
---

# Uninstall Options List Stale/Incomplete

## Problem Statement
`uninstall.php` deletes options that do not actually exist in the plugin (`yoko_lc_db_version`, `yoko_lc_post_types`, `yoko_lc_check_timeout`) and misses options that DO exist (`yoko_lc_settings`, `yoko_lc_schema_version`, `yoko_lc_activated_at`). This means three plugin options persist in the `wp_options` table after uninstall, leaving residual data behind. This violates WordPress plugin guidelines which require complete cleanup on uninstall.

## Findings
- `uninstall.php` at lines 44-55 contains a hardcoded list of option names to delete.
- Options listed for deletion that do not exist: `yoko_lc_db_version`, `yoko_lc_post_types`, `yoko_lc_check_timeout`.
- Options that exist but are NOT listed for deletion: `yoko_lc_settings`, `yoko_lc_schema_version`, `yoko_lc_activated_at`.
- The options list appears to have been written early in development and never updated as the option names evolved.

## Proposed Solutions

### Option A: Update Options Array to Match Actual Options
- **Approach:** Audit all `add_option()`, `update_option()`, and `get_option()` calls in the codebase to build the authoritative list of option names. Update `uninstall.php` to delete exactly those options.
- **Pros:** Simple and direct; complete cleanup; easy to verify
- **Cons:** Requires manual audit; list can become stale again if not maintained
- **Effort:** Small
- **Risk:** Low

### Option B: Use Wildcard Delete for Prefixed Options
- **Approach:** Use `DELETE FROM wp_options WHERE option_name LIKE 'yoko_lc_%'` to delete all plugin options regardless of name.
- **Pros:** Catches all options automatically; future-proof; no maintenance needed
- **Cons:** Could delete options from another plugin with the same prefix (unlikely but possible); uses direct SQL instead of `delete_option()`
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `uninstall.php` (lines 44-55)

## Acceptance Criteria
- [ ] All options created by the plugin are listed in `uninstall.php` for deletion
- [ ] No non-existent option names remain in the deletion list
- [ ] After uninstall, zero `yoko_lc_*` options remain in `wp_options`
- [ ] Uninstall also removes any transients created by the plugin
- [ ] A code comment or constant documents the authoritative list of options for future maintenance

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
