---
status: complete
priority: p2
issue_id: "070"
tags: [code-review, security, php]
dependencies: []
---

# Missing ABSPATH Guard on Source Files

## Problem Statement
`LinksListTable.php` and other src/ files lack the standard `defined('ABSPATH') || exit;` guard. Direct URL access to `LinksListTable.php` triggers a PHP fatal error referencing `ABSPATH`, exposing the absolute server filesystem path.

## Findings
**File:** `src/Admin/LinksListTable.php` (references ABSPATH on line 22)
**File:** `src/Activator.php` (references ABSPATH on line 149)
Found by: security-sentinel

## Proposed Solutions
### Option A: Add ABSPATH guard to all src/ files
- **Approach:** Add `defined('ABSPATH') || exit;` after `declare(strict_types=1);` in all PHP files.
- **Effort:** Small

## Acceptance Criteria
- [ ] Direct access to any src/ PHP file does not expose server paths

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
