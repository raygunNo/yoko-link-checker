---
status: complete
priority: p3
issue_id: "079"
tags: [code-review, php, rfc-compliance]
dependencies: []
---

# CSV Content-Disposition Filename Not Quoted

## Problem Statement
The Content-Disposition header sets `filename=` without quotes around the value, violating RFC 6266.

## Findings
**File:** `src/Admin/ResultsPage.php` line 254
Found by: wp-php-reviewer, security-sentinel

## Proposed Solutions
### Option A: Quote the filename
- **Approach:** Change to `filename="' . $filename . '"`.
- **Effort:** Small

## Acceptance Criteria
- [ ] Filename is quoted per RFC 6266

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
