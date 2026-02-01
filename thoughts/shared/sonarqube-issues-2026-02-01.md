# SonarQube Issues Breakdown

**Project:** stephenfeather_sermon-browser
**Date:** 2026-02-01
**Source:** SonarCloud MCP API

---

## Summary

| Category | Count |
|----------|-------|
| **Total Issues** | 1,489 |
| Code Smells | 226 |
| Bugs | 26 |
| Vulnerabilities | 7 |
| Security Hotspots | 0 |

| Severity | Count |
|----------|-------|
| Blocker | 4 |
| Critical | 59 |
| Major | 147 |
| Minor | 49 |
| Info | 0 |
| **Total Violations** | 259 |

---

## BLOCKER Issues (4 total) - FIXED 2026-02-01

All 4 were in `tests/Unit/Install/UpgraderTest.php` - test cases missing assertions.

| Line | Rule | Message | Status |
|------|------|---------|--------|
| 25 | php:S2699 | Add at least one assertion to this test case | FIXED |
| 50 | php:S2699 | Add at least one assertion to this test case | FIXED |
| 75 | php:S2699 | Add at least one assertion to this test case | FIXED |
| 99 | php:S2699 | Add at least one assertion to this test case | FIXED |

**Fix:** Added `$this->assertTrue(true, '...')` after Brain\Monkey expectation-based tests.

---

## CRITICAL Issues (59 total) - OPEN

### Cognitive Complexity (php:S3776) - Refactor needed

Functions exceeding the allowed complexity of 15:

| File | Line | Function | Complexity |
|------|------|----------|------------|
| `src/Admin/Pages/SermonEditorPage.php` | 670 | render() inner | 88 |
| `sermon.php` | 282 | sb_display_sermons() | 42 |
| `sermon.php` | 629 | sb_shortcode() | 41 |
| `sermon.php` | 742 | sb_create_multi_sermon_query() | 31 |
| `src/Frontend/FilterRenderer.php` | 149 | renderDropdownFilters() | 30 |
| `src/Frontend/FilterRenderer.php` | 532 | renderDynamicFilters() | 28 |
| `sermon.php` | 180 | sb_sermon_init() | 26 |
| `src/Frontend/Widgets/SermonWidget.php` | 110 | widget() | 26 |
| `src/Admin/Pages/SermonEditorPage.php` | 81 | handleFormSubmission() | 25 |
| `src/Frontend/Widgets/PopularWidget.php` | 27 | widget() | 25 |
| `src/Admin/Pages/FilesPage.php` | 312 | renderFilesTab() | 23 |
| `src/Widgets/SermonsWidget.php` | 49 | widget() | 22 |
| `src/Ajax/LegacyAjaxHandler.php` | 267 | handleFileUpload() | 22 |
| `src/Frontend/BibleText.php` | 194 | getBibleText() | 22 |
| `src/Podcast/PodcastFeed.php` | 127 | generateFeed() | 19 |
| `src/Install/Upgrader.php` | 240 | upgradeDatabaseSchema() | 18 |
| `src/Ajax/LegacyAjaxHandler.php` | 154 | handleSermonSave() | 16 |
| `src/Repositories/SermonRepository.php` | 606 | buildQuery() | 16 |

### Duplicate String Literals (php:S1192) - Define constants

| File | String | Occurrences | Line |
|------|--------|-------------|------|
| `src/Frontend/FilterRenderer.php` | `selected="selected"` | 9x | 552 |
| `src/Admin/Pages/OptionsPage.php` | `checked="checked"` | 8x | 547 |
| `src/Admin/Pages/PreachersPage.php` | `images/` | 7x | 105 |
| `src/Frontend/Widgets/PopularWidget.php` | `").html("` | 6x | 63 |
| `src/Blocks/BlockRegistry.php` | `/style-index.css` | 4x | 136 |
| `src/Frontend/FilterRenderer.php` | `[All]` | 4x | 552 |
| `src/REST/Endpoints/SeriesController.php` | `Unique identifier for the series.` | 4x | 77 |
| `src/REST/Endpoints/SeriesController.php` | `Series not found.` | 4x | 327 |
| `src/Admin/Pages/OptionsPage.php` | Permission error message | 3x | 41 |
| `src/Admin/Pages/FilesPage.php` | `admin.php?page=sermon-browser/new_sermon.php&getid3=` | 3x | 131 |
| `src/Admin/Pages/FilesPage.php` | `File name` | 3x | 429 |
| `src/Admin/Pages/FilesPage.php` | `File type` | 3x | 430 |
| `src/Admin/Pages/SermonEditorPage.php` | `admin.php?page=sermon-browser/sermon.php` | 3x | 696 |
| `src/Admin/Pages/SermonEditorPage.php` | `selected="selected"` | 3x | 850 |
| `src/Install/Upgrader.php` | `00:00` | 3x | 316 |
| `src/Podcast/PodcastHelper.php` | `D, d M Y H:i:s O` | 3x | 43 |
| `src/Widgets/SermonsWidget.php` | `[All]` | 3x | 199 |
| `src/Widgets/TagCloudWidget.php` | `Sermon Browser Tags` | 3x | 28 |
| `src/Frontend/FileDisplay.php` | `http://` | 3x | 57 |
| `src/Frontend/FileDisplay.php` | `https://` | 3x | 57 |
| `src/Frontend/Widgets/PopularWidget.php` | jQuery selector string | 3x | 123-124 |
| `src/Repositories/FileRepository.php` | ` LIMIT %d` | 3x | 189 |
| `src/Repositories/FileRepository.php` | ` LIMIT %d OFFSET %d` | 3x | 547 |
| `src/REST/Endpoints/FilesController.php` | `File not found.` | 3x | 275 |
| `src/REST/Endpoints/PreachersController.php` | `Unique identifier for the preacher.` | 3x | 76 |
| `src/REST/Endpoints/PreachersController.php` | `Preacher not found.` | 3x | 321 |
| `src/REST/Endpoints/SermonsController.php` | `Unique identifier for the sermon.` | 3x | 75 |
| `src/REST/Endpoints/SermonsController.php` | `Sermon not found.` | 3x | 376 |
| `src/REST/Endpoints/ServicesController.php` | `Unique identifier for the service.` | 3x | 76 |
| `src/REST/Endpoints/ServicesController.php` | `Service not found.` | 3x | 276 |
| `sermon.php` | `0.6.0` | 3x | 355 |

### Duplicate HTML IDs (Web:S7930) - Accessibility bugs

| File | Issue | Lines |
|------|-------|-------|
| `src/Admin/Pages/FilesPage.php` | Duplicate id "search" | 499, 503 |
| `src/Admin/Pages/FilesPage.php` | Duplicate id "file<?php echo $file->id ?>" | 437, 472 |
| `src/Admin/Pages/FilesPage.php` | Duplicate id "<?php echo $file->id ?>" | 439, 474 |
| `src/Admin/Pages/FilesPage.php` | Duplicate id "link<?php echo $file->id; ?>" | 443, 486 |

---

## Priority Fix Order

### Quick Wins (1-2 hours)
1. ~~**UpgraderTest.php** - Add assertions to 4 tests~~ DONE
2. **FilesPage.php** - Fix duplicate HTML IDs (accessibility)
3. **Constants class** - Create `src/Constants.php` for repeated strings

### Medium Effort (1-2 days)
4. **FilterRenderer.php** - Split large functions, extract constants
5. **SermonEditorPage.php** - Major refactoring of render() method
6. **REST Controllers** - Create shared message constants

### Larger Refactoring (ongoing)
7. **sermon.php** - Break down sb_display_sermons(), sb_shortcode()
8. **Widget classes** - Reduce complexity in widget() methods
9. **LegacyAjaxHandler** - Consider splitting into smaller handlers

---

## Suggested Constants Class

```php
<?php
namespace SermonBrowser;

class Constants
{
    // HTML attributes
    public const SELECTED = 'selected="selected"';
    public const CHECKED = 'checked="checked"';

    // UI strings
    public const ALL_FILTER = '[All]';

    // Date formats
    public const RFC822_DATE = 'D, d M Y H:i:s O';
    public const DEFAULT_TIME = '00:00';

    // Paths
    public const IMAGES_PATH = 'images/';
    public const STYLE_INDEX_CSS = '/style-index.css';

    // Admin URLs
    public const SERMON_PAGE_URL = 'admin.php?page=sermon-browser/sermon.php';
    public const NEW_SERMON_GETID3_URL = 'admin.php?page=sermon-browser/new_sermon.php&getid3=';

    // Error messages
    public const SERMON_NOT_FOUND = 'Sermon not found.';
    public const PREACHER_NOT_FOUND = 'Preacher not found.';
    public const SERIES_NOT_FOUND = 'Series not found.';
    public const SERVICE_NOT_FOUND = 'Service not found.';
    public const FILE_NOT_FOUND = 'File not found.';
    public const NO_PERMISSION = 'You do not have the correct permissions to edit the SermonBrowser options';

    // REST API descriptions
    public const SERMON_ID_DESC = 'Unique identifier for the sermon.';
    public const PREACHER_ID_DESC = 'Unique identifier for the preacher.';
    public const SERIES_ID_DESC = 'Unique identifier for the series.';
    public const SERVICE_ID_DESC = 'Unique identifier for the service.';
}
```

---

## Notes

- SonarQube counts include both OPEN and CLOSED issues in totals
- Many CLOSED issues are from deleted legacy files (sb-includes/)
- The 259 "violations" metric counts only OPEN issues by severity
- Cognitive complexity > 15 indicates functions need refactoring
- Duplicate strings > 3 occurrences should become constants
