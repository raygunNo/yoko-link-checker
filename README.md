# Yoko Link Checker

A performant, extensible broken link checker for WordPress. Scans content for links, checks their validity, and reports issues.

**Requirements:** WordPress 6.0+ | PHP 8.0+

## Description

Yoko Link Checker helps you identify and fix broken links across your WordPress website. It scans your posts, pages, and custom post types for links, validates them, and provides detailed reports to help you maintain a healthy site.

### Features

- **Comprehensive Scanning**: Scans posts, pages, and custom post types for links
- **Efficient HTTP Checking**: Validates links using optimized HTTP requests
- **Batch Processing**: Handles large sites efficiently with batch processing
- **Status Classification**: Categorizes link issues (broken, redirect, timeout, etc.)
- **Admin Dashboard**: Clean, intuitive interface for managing link health
- **Detailed Reports**: View all links with filtering and sorting options
- **Auto-scan Support**: Schedule automatic scans via WP-Cron
- **Extensible Architecture**: Built with clean, modular code for easy customization

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher

## Installation

### From GitHub

1. Download the latest release from the [Releases](https://github.com/Yoko-Co/yoko-link-checker/releases) page
2. Upload the `yoko-link-checker` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

### Manual Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/Yoko-Co/yoko-link-checker.git
   ```
2. Copy the folder to your WordPress plugins directory
3. Activate the plugin in WordPress admin

## Usage

1. Navigate to **Tools > Link Checker** in your WordPress admin
2. Click **Start Scan** to begin scanning your content
3. Review results in the dashboard and results page
4. Fix broken links as needed in your content

## Configuration

The plugin provides several configuration options:

- **Post Types**: Select which post types to scan
- **Check Timeout**: Set timeout for HTTP requests
- **Auto-scan**: Enable/disable automatic scheduled scans
- **Scan Frequency**: Set how often auto-scans run

## Architecture

The plugin follows a clean, modular architecture:

```
src/
├── Admin/           # Admin UI components
├── Checker/         # Link validation logic
├── Extractor/       # Content extraction
├── Model/           # Data models
├── Repository/      # Data persistence
├── Scanner/         # Scan orchestration
└── Util/            # Utility classes
```

## Development

### Project Structure

- `yoko-link-checker.php` - Main plugin file
- `src/` - PHP source code
- `assets/` - CSS and JavaScript files
- `templates/` - Admin page templates
- `uninstall.php` - Cleanup on uninstall

### Coding Standards

This plugin follows WordPress Coding Standards. To check your code:

```bash
composer install
composer run phpcs
```

## Changelog

### 1.0.1
- Fixed AJAX action name mismatch preventing scanner from starting
- Fixed incorrect namespace reference causing status check errors
- Added `YOKO_LC_DEBUG` constant to control logging (disabled by default)

### 1.0.0
- Initial release
- Content scanning for posts, pages, and custom post types
- Link validation with HTTP checking
- Admin dashboard with scan management
- Results page with filtering and sorting
- Auto-scan scheduling support

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

Developed by [Yoko Co.](https://yokoco.com)
