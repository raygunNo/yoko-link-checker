---
status: complete
priority: p2
issue_id: "095"
tags: [code-review, dead-code, php]
dependencies: []
---

# Dead Repository Methods (~180 LOC)

## Problem Statement
Multiple repository methods have zero callers in the codebase. These were part of an earlier API design that was superseded.

## Findings
**File:** `src/Repository/LinkRepository.php`
- `find_or_create()` - 0 callers (~20 LOC)
- `delete_by_source()` - 0 callers
- `get_by_url()` - 0 callers
- `get_by_source()` - 0 callers
- `get_with_status()` - 0 callers
- `count_with_status()` - 0 callers (~130 LOC total)

**File:** `src/Repository/ScanRepository.php`
- `get_latest()` - 0 callers
- `get_recent()` - 0 callers (~25 LOC total)

Found by: code-simplicity-reviewer

## Proposed Solutions
### Option A: Remove all dead methods
- **Approach:** Delete all 8 methods. Re-add if actual callers emerge.
- **Effort:** Small

## Acceptance Criteria
- [ ] All dead repository methods removed
- [ ] No remaining references to deleted methods

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
