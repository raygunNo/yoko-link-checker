---
status: complete
priority: p3
issue_id: "051"
tags: [code-review, dead-code]
dependencies: []
---

# Dead Deprecated get_all_for_export() Method

## Problem Statement

`LinkRepository::get_all_for_export()` is marked deprecated with zero callers. It wraps `stream_for_export()` into an array, defeating the streaming purpose.

## Findings

**File:** `src/Repository/LinkRepository.php` lines 596-611

Found by: code-simplicity-reviewer

## Proposed Solutions

### Option A: Delete it
- **Effort:** Small

## Acceptance Criteria
- [ ] Method removed
- [ ] No references remain

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
