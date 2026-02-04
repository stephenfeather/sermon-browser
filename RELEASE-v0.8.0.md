# Sermon Browser v0.8.0

**Release Date:** February 3, 2026
**Type:** Major Feature & Security Update
**Stability:** Beta

---

## 🔒 Security Enhancements (Critical)

This release addresses multiple security vulnerabilities and implements comprehensive protection mechanisms:

### SQL Injection Prevention
- **Fixed:** SQL injection vulnerability in sermon sorting functionality
- **Impact:** Prevents malicious actors from executing arbitrary database queries
- **Implementation:** All database queries now use WordPress `$wpdb->prepare()` with proper parameterization

### SSRF Protection
- **Fixed:** Server-Side Request Forgery (SSRF) vulnerability in file download handlers
- **Impact:** Prevents attackers from making the server access internal/external resources
- **Implementation:** URL validation and allowlist-based domain checking

### CSRF Protection
- **Added:** Cross-Site Request Forgery (CSRF) protection for all admin forms and actions
- **Implementation:** WordPress nonce verification on all state-changing operations
- **Scope:** Admin pages, AJAX handlers, and REST API endpoints

### XSS Mitigation
- **Enhanced:** Output escaping throughout the plugin
- **Fixed:** Multiple potential cross-site scripting (XSS) vectors
- **Implementation:** Proper use of `esc_html()`, `esc_attr()`, `esc_url()`, and `wp_kses()` functions

### Path Traversal Protection
- **Fixed:** Path traversal vulnerabilities in file handling operations
- **Impact:** Prevents unauthorized file access outside designated directories
- **Implementation:** Path normalization and validation in file upload/download handlers

### Security Headers
- **Added:** Security headers for all plugin responses
- **Headers Implemented:**
  - `X-Content-Type-Options: nosniff` - Prevents MIME-type sniffing attacks
  - `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking attacks
- **Scope:** Admin pages and REST API endpoints

### REST API Rate Limiting
- **Added:** Comprehensive rate limiting system for REST API endpoints
- **Limits:**
  - Anonymous users: 60 requests/minute (20 for search)
  - Authenticated users: 120 requests/minute (40 for search)
- **Features:**
  - Per-IP tracking using WordPress transients
  - HTTP 429 responses when limits exceeded
  - Standard rate limit headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`)
  - Configurable via filters

---

## ✨ New Features

### Full Site Editing (FSE) Support

Complete integration with WordPress Full Site Editing:

- **Block Templates:** Archive template for sermon post type (`archive-sermon.html`)
- **Template System:** FSE-compatible template structure
- **Theme.json Support:** Prepared for future theme integration
- **Block-Based Editing:** Full Gutenberg editor support for sermon layouts

### New Gutenberg Blocks

Four powerful new blocks for building sermon pages:

#### 1. **Sermon Grid Block** (`sermon-browser/sermon-grid`)
- Responsive grid layout for displaying multiple sermons
- Customizable columns (2-4)
- Filtering and sorting options
- Perfect for archive pages and landing pages

#### 2. **Profile Block** (`sermon-browser/profile-block`)
- Display speaker/preacher profiles with bio and photo
- Customizable layout (image left/right, stacked)
- Support for social media links
- Ideal for "Meet Our Preachers" pages

#### 3. **Sermon Media Block** (`sermon-browser/sermon-media`)
- Dedicated audio/video player for sermon media files
- Multiple format support (MP3, MP4, WebM)
- Download links and playback controls
- Accessible player interface

#### 4. **Sermon Filters Block** (`sermon-browser/sermon-filters`)
- Dynamic AJAX-powered filtering controls
- Filter by preacher, series, service, date range, and tags
- Live search without page refresh
- Responsive mobile-friendly design

### Block Patterns

Five pre-built block patterns for common sermon page layouts:

1. **Featured Sermon Hero** (`sermon-browser/featured-sermon-hero`)
   - Full-width hero section showcasing latest sermon
   - Call-to-action buttons
   - Background image support

2. **Sermon Archive Page** (`sermon-browser/sermon-archive-page`)
   - Complete archive layout with filters and grid
   - Pagination included
   - Ready to use out of the box

3. **Preacher Spotlight** (`sermon-browser/preacher-spotlight`)
   - Highlight featured preacher with bio and recent sermons
   - Photo and profile integration
   - Recent sermons list

4. **Popular This Week** (`sermon-browser/popular-this-week`)
   - Display most popular sermons from the last 7 days
   - Automatic sorting by plays/downloads
   - Configurable limit

5. **Tag Cloud Sidebar** (`sermon-browser/tag-cloud-sidebar`)
   - Sidebar-ready tag cloud
   - Popular topic navigation
   - Click-to-filter functionality

### AJAX Dynamic Filtering

- **Real-time Filtering:** No page reload required when filtering sermons
- **Multiple Criteria:** Combine preacher, series, service, and date filters
- **Responsive UI:** Smooth animations and loading indicators
- **Performance:** Optimized queries with caching

---

## 🔧 Developer Experience

### Testing Infrastructure

- **PHPUnit v11:** Upgraded to the latest PHPUnit version
- **ParaTest Integration:** Parallel test execution for faster CI/CD
- **Test Coverage:** 1,416 tests passing across 74 test files
- **Test Suites:**
  - Unit tests (comprehensive repository and service coverage)
  - Integration tests
  - Security-specific test suite

### Modern PHP Architecture

- **PSR-4 Autoloading:** Fully namespaced codebase
- **Strict Types:** Type declarations throughout (`declare(strict_types=1)`)
- **Repository Pattern:** Clean data access layer
- **Dependency Injection:** Container-based service management

### Code Quality Tools

- **PHPStan:** Level 8 static analysis
- **PHP_CodeSniffer:** WordPress Coding Standards compliance
- **Automated Testing:** CI-ready test suite with coverage reporting

---

## 📊 Release Statistics

- **Commits:** 15
- **Files Changed:** 150+
- **Tests:** 1,416 passing
- **Test Files:** 74
- **New Blocks:** 4
- **Block Patterns:** 5
- **Security Fixes:** 7 critical vulnerabilities addressed
- **Breaking Changes:** 0

---

## ⚠️ Important Notes

### No Breaking Changes

This release maintains full backward compatibility with v0.7.x. All existing functionality continues to work as expected.

### No Migration Required

- Existing templates and settings are preserved
- Database schema unchanged
- No user action required for upgrade

### Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.0 or higher
- **Recommended:** WordPress 6.4+ with PHP 8.2

---

## 🚀 Upgrade Instructions

### Automatic Update (Recommended)

1. Navigate to **Plugins** in WordPress admin
2. Find Sermon Browser in the plugin list
3. Click **Update Now**
4. The plugin will update automatically

### Manual Update

1. Deactivate the current plugin
2. Delete the old plugin files (settings are preserved in database)
3. Upload the new v0.8.0 plugin files
4. Activate the plugin
5. Verify functionality on your sermon pages

### Post-Update

- Clear any caching plugins (WP Super Cache, W3 Total Cache, etc.)
- Test REST API endpoints if you have custom integrations
- Review new blocks in the Gutenberg editor

---

## 🔮 What's Next

### Upcoming in v0.9.0

- Advanced sermon search with autocomplete
- Sermon transcription support
- Speaker dashboard improvements
- Enhanced analytics and statistics

### Roadmap to v1.0.0

- Complete template system overhaul (removing `eval()` usage)
- Advanced caching layer
- GraphQL API support
- Headless CMS compatibility

---

## 🙏 Credits

### Security Researchers

Thank you to the security researchers who responsibly disclosed vulnerabilities:
- SQL injection in sorting (internal audit)
- SSRF in file handlers (internal audit)
- Path traversal issues (internal audit)

### Contributors

Special thanks to all contributors who helped test and refine this release.

### Testing

This release underwent extensive testing:
- 1,416 automated tests
- Manual security audit
- Cross-browser testing
- WordPress 6.0-6.4 compatibility testing
- PHP 8.0-8.2 compatibility testing

---

## 📚 Documentation

- [Installation Guide](README.md#installation)
- [Block Documentation](docs/blocks.md)
- [Security Best Practices](docs/security.md)
- [Developer Guide](docs/development.md)
- [REST API Reference](docs/api.md)

---

## 🐛 Bug Reports & Support

- **GitHub Issues:** [Report bugs or request features](https://github.com/feather-design-works/developer-sermon-browser/issues)
- **Support Forum:** [WordPress Plugin Support](https://wordpress.org/support/plugin/sermon-browser/)
- **Documentation:** [User Guide](https://sermonbrowser.com/docs/)

---

## 📄 License

Sermon Browser is licensed under the [GNU General Public License v3.0 or later](LICENSE).

---

## 🔐 Security Disclosure

If you discover a security vulnerability, please email security@sermonbrowser.com. Do not open a public GitHub issue for security matters.

**Response Time:** We aim to respond to security reports within 48 hours and provide fixes within 7 days for critical vulnerabilities.

---

**Full Changelog:** [v0.7.x...v0.8.0](https://github.com/feather-design-works/developer-sermon-browser/compare/v0.7.0...v0.8.0)
