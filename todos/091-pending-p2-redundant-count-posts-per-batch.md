---
status: pending
priority: p2
issue_id: "091"
tags: [code-review, performance, database]
dependencies: []
---

# Redundant count_posts() Called Every Discovery Batch

## Problem Statement
`process_discovery_batch()` calls `$this->content_discovery->count_posts()` on every batch iteration, executing a `SELECT COUNT(*)` on the posts table each time. The count never changes during a scan. For 50,000 posts at batch size 50, this runs 1,000 times.

## Findings
**File:** `src/Scanner/BatchProcessor.php` line 4397
Found by: performance-oracle

The total was already set at scan start in `ScanOrchestrator::start_scan()` and stored in `$scan->total_posts`.

## Proposed Solutions
### Option A: Use scan record's total_posts
- **Approach:** Read `$scan->total_posts` from the scan record instead of re-querying.
- **Effort:** Small

## Acceptance Criteria
- [ ] count_posts() not called per batch during discovery
- [ ] Total post count still accurate in progress tracking

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
