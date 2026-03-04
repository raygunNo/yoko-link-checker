---
status: complete
priority: p3
issue_id: "022"
tags: [code-review, quality, simplification]
dependencies: []
---

# Inline Debug Logging Should Use Logger Class

## Problem Statement
There are 30+ inline `if (defined('YOKO_LC_DEBUG') && YOKO_LC_DEBUG) { error_log(...); }` blocks scattered across multiple files, despite a Logger class already existing that gates on `YOKO_LC_DEBUG` internally. Each inline block also carries a `phpcs:ignore` comment. This duplication inflates the codebase by approximately 95 lines and adds noise with 30 suppression comments.

## Findings
- `ScanOrchestrator.php`: 16 inline debug logging blocks
- `BatchProcessor.php`: 6 inline debug logging blocks
- `AjaxHandler.php`: 7 inline debug logging blocks
- Each block follows the pattern: `if (defined('YOKO_LC_DEBUG') && YOKO_LC_DEBUG) { error_log(...); }` with an accompanying `phpcs:ignore` comment
- The existing `Logger` class already checks the `YOKO_LC_DEBUG` constant internally, making these inline checks redundant

## Proposed Solutions

### Option A: Replace All Inline Blocks with Logger Calls
- **Approach:** Replace all 30+ inline debug logging blocks with appropriate `Logger::debug()` or `Logger::info()` calls. The Logger class already handles the `YOKO_LC_DEBUG` gate, so each multi-line block can become a single-line call. This removes approximately 95 lines of code and 30 `phpcs:ignore` comments.
- **Effort:** Medium

## Technical Details
- **Affected files:**
  - `src/Scanner/ScanOrchestrator.php` (16 inline blocks)
  - `src/Scanner/BatchProcessor.php` (6 inline blocks)
  - `src/Admin/AjaxHandler.php` (7 inline blocks)

## Acceptance Criteria
- [ ] All inline `if (defined('YOKO_LC_DEBUG')...)` blocks are replaced with Logger method calls
- [ ] No `phpcs:ignore` comments remain for the replaced logging blocks
- [ ] Logger class methods (`debug()`, `info()`) are used with appropriate log levels
- [ ] Debug output remains functionally equivalent when `YOKO_LC_DEBUG` is enabled
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
