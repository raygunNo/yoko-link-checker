---
status: complete
priority: p2
issue_id: "063"
tags: [code-review, data-integrity, php]
dependencies: []
---

# Inconsistent UTC/Local Timezone in last_checked Writes

## Problem Statement
The `last_checked` column is written with mixed timezone bases. Some paths use `current_time('mysql')` (local), others use `current_time('mysql', true)` (UTC). This corrupts sort ordering in queries using `ORDER BY u.last_checked DESC`.

## Findings
**File:** `src/Scanner/BatchProcessor.php`
- Line 290: `current_time('mysql', true)` -- parallel check (UTC)
- Line 407: `current_time('mysql')` -- sequential check (local)
- Line 436: `current_time('mysql', true)` -- error marking (UTC)
- Line 457: `current_time('mysql')` -- internal check (local)
Found by: schema-drift-detector, wp-php-reviewer

## Proposed Solutions
### Option A: Standardize to local time
- **Approach:** Change lines 290 and 436 from `current_time('mysql', true)` to `current_time('mysql')` to match WordPress convention.
- **Effort:** Small

## Acceptance Criteria
- [ ] All last_checked writes use the same timezone convention

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
