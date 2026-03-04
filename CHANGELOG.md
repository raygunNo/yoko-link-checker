# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-03-03

Addresses all findings from comprehensive multi-agent code review (5 P1 Critical, 16 P2 Important, 13 P3 Nice-to-Have).

### Added
- Parallel HTTP requests for external URL checking via `Requests::request_multiple()`
- SSRF protection blocking private/internal IP ranges (127.x, 10.x, 192.168.x, etc.)
- Concurrent batch execution protection via transient-based locks
- Stale scan recovery with 30-minute timeout auto-failing stuck scans
- Database schema version tracking for automatic upgrades
- Composite index `(status, is_ignored, id)` for improved query performance
- Return value checks on all `$wpdb->insert()` calls
- `manage_options` fallback to capability checks for settings
- Polling timer cleanup on page unload
- AJAX response validation in JavaScript
- `set_service()` method for test dependency injection
- Localized all hard-coded English strings in JavaScript

### Changed
- Dashboard now uses single `GROUP BY` query instead of 16+ `COUNT` queries
- CSV export streams with chunked queries for constant memory usage
- Cron schedules filter registered persistently in `boot()` method
- Exception messages sanitized in AJAX responses (no sensitive data leakage)
- Consolidated 30+ inline debug blocks into Logger calls
- Moved raw SQL from DashboardPage to LinkRepository
- StatusClassifier injected into BatchProcessor (proper DI)
- TRUNCATE operations wrapped in transaction with error handling
- Improved PHP coding standards compliance (PSR-2)
- Enhanced error logging with structured context arrays

### Fixed
- **P1**: Ignore/unignore now targets correct table (`urls.is_ignored`, not `links`)
- **P1**: Removed batch processing from AJAX status poll (delegated to WP-Cron)
- **P1**: Schema version mismatch between Plugin.php and Activator.php
- **P1**: Ghost property `$url->redirect_url` → `$url->final_url`
- **P2**: TOCTOU race condition in `find_or_create()` URL logic
- **P2**: Associated links now cleaned up on URL deletion
- **P2**: Stale options list in `uninstall.php` updated
- **P2**: N+1 query pattern in discovery phase (post cache priming)
- **P2**: N+1 queries in LinksListTable (batch post cache priming)
- Closing brace positioning per PSR-2
- `count()` usage inside loop conditions
- Equals sign alignment in variable assignments
- Associative array formatting (multi-line requirement)

### Removed
- Unused `UrlValidator` class (205 LOC dead code)
- Unused `ExtractorRegistry`/`Interface` methods
- Duplicate URL-skip logic (consolidated from 3 locations to 1)
- Count method aliases from `UrlRepository`
- Speculative code for unbuilt features
- Dead grouped `yoko_lc_settings` option

## [1.0.8] - 2026-03-03

### Fixed
- Internal URLs that can't be verified via WordPress functions now fall back to HTTP check
- Internal 404 pages are now correctly flagged as "broken" instead of "warning"
- Custom routes, plugin pages, and archive URLs are now properly verified

### Added
- `check_internal_url_via_http()` method for fallback HTTP verification
- `yoko_lc_internal_http_args` filter for customizing internal HTTP request arguments
- Short timeout (3 seconds) for internal HTTP checks to prevent delays

## [1.0.7] - 2026-03-03

### Changed
- Renamed "Broken Links" submenu to "Reports" for broader reporting functionality
- Page title changed from "Broken Links" to "Link Reports"
- Renamed "Anchor Text" column to "Link Text" for clarity
- CSV export now includes "Source URL" column with actual permalinks
- Improved CSV column names: "Broken URL", "Source URL", "Source Title", "Source Type", "Error Details"
- CSV columns reordered for better remediation workflow

### Removed
- Removed bulk actions (Ignore, Un-ignore, Recheck) to simplify MVP interface
- Removed checkbox column from results table
- Removed bulk action processing code

## [1.0.6] - 2026-03-03

### Fixed
- Fixed TypeError in bulk actions: `LinkRepository::update()` expected Link object, received integer
- Added `update_by_id()` method to LinkRepository for updating by ID with data array

## [1.0.5] - 2026-03-03

### Fixed
- Fixed CSV export outputting HTML instead of CSV data (headers sent before output)
- Fixed bulk actions (Ignore, Un-ignore, Recheck) causing white screen or not working
- Changed bulk actions form method from GET to POST
- Added redirect after bulk action processing to prevent re-processing on refresh
- Added `manage_options` capability fallback for bulk actions

### Added
- Status badges now have tooltips with descriptions explaining what each status means
- Warning, Blocked, Timeout, Error, and Redirect statuses show detailed explanations
- Error messages from the server are now displayed in status tooltips

## [1.0.4] - 2026-03-03

### Fixed
- Fixed "Export to CSV" not working on results page
- Fixed "Clear All Data" button not working

### Added
- CSV export now includes all link data with proper UTF-8 encoding
- Info notice reminding users to keep the page open during scans

### Changed
- Improved card styling with accent border and enhanced shadow
- Stat cards now have subtle hover animation
- Updated border radius and shadow for modern appearance

## [1.0.3] - 2026-03-03

### Fixed
- Internal URLs are now properly checked for broken links instead of being skipped
- Uses WordPress functions (`url_to_postid`, `get_post`, etc.) to validate internal links
- Detects links to deleted, draft, or unpublished posts

### Changed
- Internal URL validation no longer requires HTTP requests (prevents PHP worker deadlocks)
- Unverifiable internal URLs (custom routes, archives) marked as warnings for manual review

## [1.0.2] - 2026-03-03

### Fixed
- Fixed undefined property `redirect_url` warning (should be `final_url`)
- Fixed upstream timeouts when checking internal URLs by skipping self-referential requests

### Changed
- Internal URLs are now automatically marked as valid without HTTP requests
- This prevents PHP worker deadlocks on limited-resource hosts

## [1.0.1] - 2026-03-03

### Fixed
- Fixed AJAX action name mismatch preventing scanner from starting (`ylc_` → `yoko_lc_` prefix)
- Fixed incorrect namespace reference (`Jeremie\YokoLinkChecker` → `YokoLinkChecker`)

### Changed
- Added `YOKO_LC_DEBUG` constant to control plugin logging (disabled by default)
- All debug logging now requires explicit opt-in via `wp-config.php`

## [1.0.0] - 2026-03-03

### Added
- Initial release of Yoko Link Checker
- Content scanning for posts, pages, and custom post types
- Link validation with HTTP checking
- Comprehensive status classification (broken, redirect, timeout, etc.)
- Admin dashboard with scan management
- Results page with filtering and sorting
- Auto-scan scheduling via WP-Cron
- Batch processing for large sites
- Extensible extractor architecture
- Clean uninstall with data removal options
- WordPress Coding Standards compliance
- PHP 8.0+ support
- WordPress 6.0+ support

[Unreleased]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.7...HEAD
[1.0.7]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.6...v1.0.7
[1.0.6]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Yoko-Co/yoko-link-checker/releases/tag/v1.0.0
