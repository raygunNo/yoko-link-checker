# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/Yoko-Co/yoko-link-checker/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Yoko-Co/yoko-link-checker/releases/tag/v1.0.0
