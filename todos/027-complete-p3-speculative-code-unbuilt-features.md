---
status: complete
priority: p3
issue_id: "027"
tags: [code-review, dead-code, yagni]
dependencies: []
---

# Speculative Code for Unbuilt Features

## Problem Statement
Multiple files contain methods, constants, and factory methods that were written speculatively for features that were never built or integrated. This dead code increases maintenance burden, inflates the codebase, and can mislead developers into thinking these code paths are active. Following YAGNI (You Aren't Gonna Need It), this speculative code should be removed.

## Findings
- `ContentDiscovery::is_scannable()` and `get_modified_since()` -- never called
- `Scan::TYPE_INCREMENTAL` and `TYPE_RECHECK` constants -- never used
- `UrlChecker::check_batch()`, `get_http_client()`, `get_classifier()` -- never called
- `CheckResult::success()` factory -- bypassed; never used
- `DashboardPage::format_scan_duration()` -- duplicates `Scan::get_formatted_duration()`
- `DashboardPage::get_last_completed_scan()` -- duplicates `ScanRepository::get_last_completed()`
- `ExtractedLink` constants `TYPE_SCRIPT`, `TYPE_STYLE`, `TYPE_OTHER` -- never used
- `ExtractedLink::is_anchor()`, `is_image()`, `get_truncated_text()` -- never called
- `HttpClient::is_error()` -- never called
- `Plugin.php` methods `version()`, `path()`, `url()` -- never called

## Proposed Solutions

### Option A: Delete All Unused Methods and Constants
- **Approach:** Remove all identified unused methods, constants, and factory methods across all affected files. Each deletion should be verified by confirming zero call sites exist before removal.
- **Effort:** Medium

## Technical Details
- **Affected files:**
  - `src/Scanner/ContentDiscovery.php`
  - `src/Model/Scan.php`
  - `src/Checker/UrlChecker.php`
  - `src/Checker/CheckResult.php`
  - `src/Admin/DashboardPage.php`
  - `src/Extractor/ExtractedLink.php`
  - `src/Http/HttpClient.php`
  - `src/Plugin.php`

## Acceptance Criteria
- [ ] All identified unused methods are removed from their respective files
- [ ] All identified unused constants are removed
- [ ] Duplicate methods (`format_scan_duration()`, `get_last_completed_scan()`) are removed from `DashboardPage`
- [ ] Callers of duplicate methods (if any) are updated to use the canonical versions
- [ ] No references to removed code remain in the codebase
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
