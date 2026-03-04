---
status: pending
priority: p3
issue_id: "100"
tags: [code-review, architecture, database]
dependencies: []
---

# Scan Cursor Stored in Both wp_options and Scan Record

## Problem Statement
Scan progress cursors are stored as `wp_options` entries (e.g., `yoko_lc_scan_{$id}_cursor_discovery`) AND in the scans table (`last_post_id`, `last_url_id`). The options-based cursor is authoritative while scan record columns become stale. Cleanup requires deleting 3 options per scan.

## Findings
**File:** `src/Scanner/ScanOrchestrator.php` lines 5518-5562
Found by: architecture-strategist

## Proposed Solutions
### Option A: Use scan record columns as sole cursor
- **Approach:** Remove options-based cursors. Use `last_post_id`/`last_url_id` from the scan record. Add `last_activity` as a column if needed.
- **Effort:** Medium

## Acceptance Criteria
- [ ] Single source of truth for scan cursors
- [ ] No orphaned wp_options entries from scans

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-04 | Created | From Round 4 full codebase review |
