---
status: complete
priority: p1
issue_id: "036"
tags: [code-review, database, schema-drift]
dependencies: []
---

# SCHEMA_VERSION Not Incremented for New Composite Index

## Problem Statement

`Activator::SCHEMA_VERSION` remains `'1.0.0'` despite adding a new composite index `KEY status_ignored_id (status, is_ignored, id)` to the `yoko_lc_urls` table. The `Plugin::maybe_run_activation()` method compares this constant against the stored `yoko_lc_schema_version` option to decide whether to re-run `dbDelta()`. Since the version is unchanged, existing installations will never receive the new index.

## Findings

**File:** `src/Activator.php` line 30 — `SCHEMA_VERSION = '1.0.0'` unchanged
**File:** `src/Activator.php` line 104 — new `KEY status_ignored_id (status, is_ignored, id)` added
**File:** `src/Plugin.php` line 108 — version comparison gates `Activator::activate()`

Additionally, `PRIMARY KEY (id)` uses single-space formatting on all three tables (lines 97, 120, 144). WordPress `dbDelta()` requires two spaces before the parenthesis for correct parsing.

Found by: schema-drift-detector

## Proposed Solutions

### Option A: Increment version and fix PRIMARY KEY spacing (Recommended)
- **Approach:** Change `SCHEMA_VERSION` to `'1.1.0'`. Fix `PRIMARY KEY  (id)` (two spaces) on all three tables.
- **Pros:** Existing installs get the index on next page load; dbDelta parsing corrected
- **Cons:** None
- **Effort:** Small
- **Risk:** Low

## Technical Details
- **Affected files:** `src/Activator.php`

## Acceptance Criteria
- [ ] `SCHEMA_VERSION` is incremented to `'1.1.0'`
- [ ] `PRIMARY KEY  (id)` has two spaces on all three CREATE TABLE statements
- [ ] Existing installations receive the composite index after plugin update
- [ ] `dbDelta()` correctly processes schema on upgrade

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
