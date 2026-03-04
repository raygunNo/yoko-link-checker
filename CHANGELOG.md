# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
