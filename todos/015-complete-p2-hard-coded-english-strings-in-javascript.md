---
status: complete
priority: p2
issue_id: "015"
tags: [code-review, javascript, i18n]
dependencies: []
---

# Hard-Coded English Strings in JavaScript

## Problem Statement
`admin.js` contains approximately 20 hard-coded English strings including confirm dialogs, error messages, and status labels that bypass WordPress's internationalization (i18n) system. Messages like "An error occurred", "Are you sure?", and "Starting scan..." are embedded directly in JavaScript and are not translatable. This prevents the plugin from being properly localized for non-English WordPress installations and violates WordPress plugin directory guidelines.

## Findings
- `admin.js` contains multiple hard-coded English strings throughout the file.
- Strings include confirm dialog messages, error messages, success messages, and status labels.
- None of these strings use `wp.i18n.__()` or are passed via `wp_localize_script()`.
- WordPress provides `wp_localize_script()` for passing translatable strings to JavaScript.
- WordPress also provides the `wp-i18n` JavaScript package for inline translations.

## Proposed Solutions

### Option A: Use wp_localize_script() to Pass Strings
- **Approach:** Collect all user-facing strings in the PHP enqueue function and pass them to JavaScript via `wp_localize_script()` as `ylcAdmin.strings.*`. Replace all hard-coded strings in `admin.js` with references to the localized object.
- **Pros:** Simple and well-established pattern; works with all WordPress versions; translation tools can extract strings from PHP files
- **Cons:** All strings must be defined in PHP; slightly larger page payload; strings are loaded even if not all are used
- **Effort:** Medium
- **Risk:** Low

### Option B: Use wp-i18n JavaScript Package
- **Approach:** Use the `wp.i18n.__()` and `wp.i18n._n()` functions directly in JavaScript. Create a `.pot` file that includes JavaScript strings via `wp_set_script_translations()`.
- **Pros:** Modern WordPress approach; strings stay close to usage; supports pluralization in JS; better developer experience
- **Cons:** Requires WordPress 5.0+; needs build step for string extraction; more complex setup
- **Effort:** Medium
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `assets/js/admin.js` (throughout)

## Acceptance Criteria
- [ ] All user-facing strings in `admin.js` are translatable
- [ ] No hard-coded English strings remain in JavaScript for user-facing text
- [ ] Strings are extractable by standard WordPress i18n tools (e.g., `wp i18n make-pot`)
- [ ] The plugin functions correctly with a non-English WordPress locale
- [ ] A `.pot` file is generated or updated with the JavaScript strings
- [ ] Developer-only strings (console.log, etc.) may remain in English

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
