# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.8.0] - 2026-02-03

### Security
- Fixed SQL injection vulnerability in sermon sorting
- Fixed SSRF vulnerability in file download handler
- Added CSRF protection to preacher delete action
- Added XSS escaping in templates
- Added path traversal protection
- Added security headers (X-Content-Type-Options, X-Frame-Options)
- Added rate limiting to REST API endpoints

### Added
- Full Site Editing (FSE) support with block templates
- New Gutenberg blocks:
  - Sermon Grid block: Display sermons in responsive grid layout
  - Profile Block: Display preacher/speaker profiles
  - Sermon Media block: Media player for sermon audio/video
  - Sermon Filters block: Dynamic AJAX-powered sermon filtering
- 5 block patterns for common sermon layouts
- Dynamic AJAX-powered sermon filtering

### Changed
- Upgraded to PHPUnit v11 with ParaTest for parallel testing

## [0.7.0] - 2026-02-03

### Added
- New Gutenberg Blocks (Phase 5 completion):
  - Preacher List block: Display preachers with sermon counts in list or grid layout
  - Series Grid block: Display sermon series in responsive card grid
  - Sermon Player block: Embed audio player with download link for individual sermon files
- GPL 3.0 License file added to match original plugin headers

## [0.6.0] - 2026-02-03

### Added
- Comprehensive test coverage across the codebase (18 new test classes added)
- Test coverage reporting with detailed HTML coverage reports
- 100% test coverage for multiple critical classes including:
  - Admin\AdminController
  - Admin\Pages\HelpPage
  - Admin\Pages\DropdownFilterRenderer
  - Admin\Pages\PreachersPage
  - Admin\Pages\SermonEditorPage
  - Admin\Pages\FilesPage
  - Admin\Ajax\LegacyAjaxHandler
  - Frontend\FilterRenderer
  - Frontend\BibleText
  - Install\DefaultTemplates
  - Services\Container
  - Utilities\HelperFunctions

### Changed
- Improved test coverage across all major components
- Repository classes now at 93% coverage
- Admin pages now have comprehensive test coverage
- Frontend components fully tested

### Fixed
- Test coverage gaps identified and addressed
- Improved reliability through expanded test suite

## [0.6.0-beta-1] - 2026-02-02

### Security
- **CSRF Protection**: Added nonce verification to LegacyAjaxHandler for all AJAX operations
- **CSRF Protection**: Added nonce verification to FileActionHandler for file upload/import
- **CSRF Protection**: Added wp_nonce_field() to UploadHelper form
- **XSS Prevention**: Escaped all output in LegacyAjaxHandler with esc_html(), esc_url(), esc_js()
- **XSS Prevention**: Escaped JavaScript-injected values in FilesPage with esc_js()
- **XSS Prevention**: Escaped file dropdown in UploadHelper with esc_html()
- **XSS Prevention**: Escaped widget output in PopularWidget (titles, series, preachers)
- **Header Injection**: Sanitized filenames in FileDownloadHandler with RFC 5987 encoding

### Changed
- LegacyAjaxHandler refactored to use operation-specific nonces matching frontend JavaScript
- All admin AJAX operations now require valid nonces

### Fixed
- Filter support wired to admin sermons page
- Multiple SonarQube code quality issues (S1448, S3776, S1142)
- Accessibility improvements (Web:S6853, Web:S5257)

## [0.6.0-alpha-1] - 2026-02-01

### Added
- Repository Layer (Phase 1): PSR-4 compliant repository pattern for database access
- Facade Pattern: Centralized database access through FileRepository, SermonRepository, PreacherRepository, SeriesRepository, ServiceRepository, TagRepository, and BookRepository
- Admin Page Classes (Phase 2): Modular admin page classes (OptionsPage, StatsPage, UpgradePage, FeedsPage, TagManagementPage, SermonEditorPage)
- AJAX API Layer (Phase 3): Modern WordPress AJAX API with pagination handlers
- REST API Layer (Phase 4): RESTful endpoints for sermons, preachers, series, services, files, tags, and search
- Gutenberg Blocks Infrastructure (Phase 5): Foundation for block-based editor support
- Template Engine (Phase 6): Safe template rendering system without eval()
- Constants Class: Consolidated duplicated string literals into SermonBrowser\Constants
- Frontend PSR-4 Classes: Modular classes for TagRenderer, TagParser, TemplateEngine, TemplateMigrator, ShortcodeIntegration
- SonarCloud Integration: Code quality workflow with GitHub Actions
- Release Packaging Workflow: Automated release package generation
- Code Coverage Reporting: PHPUnit integration with SonarCloud
- Claude Code Review Workflow: Automated code review on pull requests
- Asset Organization: Moved icons and images to assets/images directory
- Translation Organization: Moved translation files to languages/ directory

### Changed
- Complete PSR-4 Migration: All code now follows PSR-4 autoloading standards
- Frontend Modularization: Converted frontend.php functions to PSR-4 classes using Facade pattern
- Widget Refactoring: SB_Sermons_Widget and sb_widget_popular converted to use Facade pattern
- Database Access: All direct $wpdb usage converted to repository facades (sermon.php, admin.php, ajax.php, podcast.php, SermonEditorPage.php)
- JavaScript Modernization: Replaced legacy JavaScript with Unicode-aware string methods
- Code Style: Applied PSR-12 formatting across entire codebase via phpcbf
- Admin Pages: Wired admin.php to use new modular page classes
- Template System: Replaced eval()-based templates with safe Template Engine
- Security: Replaced rand() with random_int() for cryptographically secure random numbers

### Deprecated
- Template System: Legacy template format will be removed in v1.0.0 (breaking change warning added)

### Removed
- sb-includes/ directory: Completely removed after PSR-4 migration (Phase 7)
- dictionary.php: Obsoleted by Phase 6 template engine
- Direct $wpdb Usage: All instances replaced with repository facades
- Legacy require/include Parentheses: Removed per PSR-12 standards (SonarCloud S6600)
- readme.txt: Replaced with README.md

### Fixed
- Security Vulnerabilities: Fixed open redirect vulnerability and other security issues identified by SonarCloud
- PSR-12 Violations: Corrected code style violations in Gutenberg blocks and throughout codebase
- JavaScript Issues: Fixed SonarQube issues in 64.js and datePicker.js
- Code Quality: Addressed SonarCloud code quality issues
- Test Directory: Added .gitkeep to tests/Integration to ensure directory existence in CI
- SonarCloud Configuration: Removed deleted sb-includes from source paths

### Security
- Open Redirect Fix: Prevented open redirect vulnerability in URL handling
- Cryptographic Random: Replaced weak rand() with cryptographically secure random_int()
- Template Injection Prevention: Removed eval() from template system to eliminate code injection risks
- Input Validation: Enhanced security per SonarCloud security audit

## [0.5.0] - 2026-01-13

Initial tagged release with basic functionality.

[Unreleased]: https://github.com/stephenfeather/sermon-browser/compare/v0.8.0...HEAD
[0.8.0]: https://github.com/stephenfeather/sermon-browser/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/stephenfeather/sermon-browser/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/stephenfeather/sermon-browser/compare/v0.6.0-beta-1...v0.6.0
[0.6.0-beta-1]: https://github.com/stephenfeather/sermon-browser/compare/v0.6.0-alpha-1...v0.6.0-beta-1
[0.6.0-alpha-1]: https://github.com/stephenfeather/sermon-browser/compare/v0.5.0...v0.6.0-alpha-1
[0.5.0]: https://github.com/stephenfeather/sermon-browser/releases/tag/v0.5.0
