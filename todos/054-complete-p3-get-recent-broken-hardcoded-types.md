---
status: complete
priority: p3
issue_id: "054"
tags: [code-review, php, quality]
dependencies: []
---

# get_recent_broken() Hardcodes post/page Source Types

## Problem Statement

`LinkRepository::get_recent_broken()` checks `in_array($row->source_type, array('post', 'page'))` for post title display. Custom post types registered in settings won't show titles in the broken links list.

## Findings

**File:** `src/Repository/LinkRepository.php` line 720

Found by: wp-php-reviewer

## Proposed Solutions

### Option A: Remove type check
- **Approach:** All `source_type` values are valid post types. Just check `$source_id` is truthy.
- **Effort:** Small

## Acceptance Criteria
- [ ] Custom post type broken links show post titles

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
