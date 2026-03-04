---
status: pending
priority: p3
issue_id: "097"
tags: [code-review, dead-code, database]
dependencies: []
---

# YAGNI: next_check Column and PROBLEM_STATUSES Constant

## Problem Statement
The `next_check` column exists in the database schema with an index but is never written to or queried. `Url::PROBLEM_STATUSES` constant is defined but never referenced.

## Findings
**File:** `src/Activator.php` (schema definition with KEY next_check)
**File:** `src/Model/Url.php` ($next_check property, lines 58-64 PROBLEM_STATUSES)
Found by: code-simplicity-reviewer

## Proposed Solutions
### Option A: Remove in next schema version
- **Approach:** Remove next_check from schema, drop index, remove property. Remove PROBLEM_STATUSES constant. Requires schema version bump.
- **Effort:** Small (but needs migration)

## Acceptance Criteria
- [ ] next_check column removed from schema
- [ ] PROBLEM_STATUSES constant removed
- [ ] Schema version incremented

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
