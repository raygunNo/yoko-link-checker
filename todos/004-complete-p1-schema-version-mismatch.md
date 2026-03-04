---
status: complete
priority: p1
issue_id: "004"
tags: [code-review, architecture, performance]
dependencies: []
---

# Schema Version Mismatch Causes Unnecessary Activation

## Problem Statement
Two competing version tracking systems fight each other, causing unnecessary activation routines on every plugin update. `Plugin::maybe_run_activation()` (line 106) compares the stored `yoko_lc_schema_version` option against `YOKO_LC_VERSION` (currently 1.0.8), but `Activator::activate()` writes `Activator::SCHEMA_VERSION` (1.0.0) to that same option. On initial activation, the Activator writes '1.0.0', then Plugin immediately detects a mismatch (1.0.0 != 1.0.8) and re-runs activation, overwriting the stored version to '1.0.8'. On every subsequent plugin update where `YOKO_LC_VERSION` changes, activation runs again unnecessarily — executing `dbDelta` and resetting user capabilities even when no schema changes occurred. The `Activator::needs_upgrade()` method exists but is never called anywhere, indicating an incomplete migration system.

## Findings
- `Plugin::maybe_run_activation()` at line 106 compares stored version against `YOKO_LC_VERSION` (1.0.8).
- `Activator::activate()` at line 155 writes `Activator::SCHEMA_VERSION` (1.0.0) to the `yoko_lc_schema_version` option.
- On initial activation: Activator writes '1.0.0', Plugin detects mismatch, re-runs activation, overwrites to '1.0.8'.
- On every plugin update: if `YOKO_LC_VERSION` bumps, activation runs unnecessarily.
- `Activator::needs_upgrade()` at line 30 exists but is never called.
- Unnecessary `dbDelta` calls on every version bump add overhead and risk.
- Unnecessary capability resets could interfere with custom role configurations.

## Proposed Solutions

### Option A: Unify on YOKO_LC_VERSION
- **Approach:** Remove `Activator::SCHEMA_VERSION` constant entirely. Remove line 155 from `Activator::activate()` that writes the schema version (let `Plugin.php` be the sole authority for writing the stored version). This eliminates the dual-write conflict. Activation still runs on version bumps, but at least consistently.
- **Pros:** Simple change; eliminates the competing writes; consistent version tracking.
- **Cons:** Activation still runs on every plugin version bump even without schema changes; `dbDelta` and capability resets still fire unnecessarily.
- **Effort:** Small
- **Risk:** Low

### Option B: Unify on Activator::SCHEMA_VERSION with proper upgrade tracking
- **Approach:** Change `Plugin.php` line 106 to compare against `Activator::SCHEMA_VERSION` instead of `YOKO_LC_VERSION`. Only bump `SCHEMA_VERSION` when actual database schema or capability changes occur. Wire up `Activator::needs_upgrade()` to control whether activation routines execute. Keep `YOKO_LC_VERSION` for display/informational purposes only.
- **Pros:** Activation only runs when genuinely needed; no unnecessary `dbDelta`; proper separation of schema version vs. plugin version; leverages existing `needs_upgrade()` method.
- **Cons:** Requires discipline to bump `SCHEMA_VERSION` when schema changes; slightly more involved refactor.
- **Effort:** Medium
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Plugin.php` (lines 103-111)
  - `src/Activator.php` (line 30, line 155)
- **Affected components:** Plugin activation lifecycle, Schema migration system, Capability management

## Acceptance Criteria
- [ ] Single source of truth for schema/activation version tracking
- [ ] Activation routines run only once on initial install
- [ ] Activation routines run only when actual schema changes occur (not on every version bump)
- [ ] `dbDelta` does not execute unnecessarily on plugin updates
- [ ] User capabilities are not reset unnecessarily on plugin updates
- [ ] `Activator::needs_upgrade()` is wired into the activation flow or removed if not needed
- [ ] Stored version option is written by exactly one code path

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
