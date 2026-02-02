# SonarQube Issues Breakdown

**Project:** stephenfeather_sermon-browser
**Date:** 2026-02-01 (Updated 19:30)
**Source:** SonarCloud API

> **Note:** Local fixes committed but not yet pushed. SonarQube will update after push + re-analysis.

---

## Summary

| Category | Count |
|----------|-------|
| **Total Open Issues** | 214 |
| Code Smells | 186 |
| Bugs | 21 |
| Vulnerabilities | 7 |

| Severity | Count |
|----------|-------|
| Blocker | 1 |
| Critical | 30 |
| Major | 140 |
| Minor | 43 |
| Info | 0 |

---

## BLOCKER Issues (1 total)

| File | Line | Rule | Message | Status |
|------|------|------|---------|--------|
| ~~`tests/Unit/Admin/Ajax/FilePaginationAjaxTest.php`~~ | ~~440~~ | ~~php:S2699~~ | ~~Add at least one assertion to this test case~~ | **FIXED** (pending) |

---

## CRITICAL Issues (30 total)

### Cognitive Complexity (php:S3776) - 8 issues (was 9)

Functions exceeding the allowed complexity of 15:

| File | Line | Complexity | Status |
|------|------|------------|--------|
| `src/Admin/Pages/SermonEditorPage.php` | 505 | 59 | |
| `sermon.php` | 285 | 42 | |
| `sermon.php` | 535 | 41 | |
| `sermon.php` | 648 | 31 | |
| ~~`src/Frontend/FilterRenderer.php`~~ | ~~562~~ | ~~28~~ | **FIXED** (ccf1450) |
| `sermon.php` | 183 | 26 | |
| `src/Admin/Pages/SermonEditorPage.php` | 83 | 25 | |
| `src/Admin/Pages/FilesPage.php` | 313 | 23 | |
| `src/Repositories/SermonRepository.php` | 606 | 16 | |

### Duplicate String Literals (php:S1192) - 17 issues

| File | String | Count | Line |
|------|--------|-------|------|
| `src/Admin/Pages/PreachersPage.php` | `images/` | 7x | 105 |
| `src/REST/Endpoints/SeriesController.php` | `Unique identifier for the series.` | 4x | 78 |
| `src/Blocks/BlockRegistry.php` | `/style-index.css` | 4x | 136 |
| `src/Admin/Pages/OptionsPage.php` | Permission error message | 3x | 42 |
| `src/Admin/Pages/FilesPage.php` | `admin.php?page=sermon-browser/new_sermon.php&getid3=` | 3x | 132 |
| `src/Admin/Pages/SermonEditorPage.php` | `selected="selected"` | 3x | 578 |
| `src/Admin/Pages/SermonEditorPage.php` | `admin.php?page=sermon-browser/sermon.php` | 3x | 753 |
| `src/Frontend/Widgets/PopularWidget.php` | `jQuery("#popular_` | 3x | 173 |
| `src/Frontend/Widgets/PopularWidget.php` | `jQuery("#sb_popular_wrapper` | 3x | 179 |
| `src/REST/Endpoints/PreachersController.php` | `Unique identifier for the preacher.` | 3x | 77 |
| `src/REST/Endpoints/SermonsController.php` | `Unique identifier for the sermon.` | 3x | 76 |
| `src/REST/Endpoints/ServicesController.php` | `Unique identifier for the service.` | 3x | 77 |
| `src/Repositories/FileRepository.php` | ` LIMIT %d` | 3x | 189 |
| `src/Repositories/FileRepository.php` | ` LIMIT %d OFFSET %d` | 3x | 547 |
| `sermon.php` | `not found` | 3x | 214 |
| `sermon.php` | `sermon-browser/new_sermon.php` | 3x | 397 |

### Duplicate HTML IDs (Web:S7930) - 3 issues

| File | Issue | Lines |
|------|-------|-------|
| `src/Admin/Pages/FilesPage.php` | Duplicate id `file<?php echo $file->id ?>` | 438, 473 |
| `src/Admin/Pages/FilesPage.php` | Duplicate id `<?php echo $file->id ?>` | 440, 475 |
| `src/Admin/Pages/FilesPage.php` | Duplicate id `link<?php echo $file->id; ?>` | 444, 487 |

### Other Critical Issues

| File | Line | Rule | Message | Status |
|------|------|------|---------|--------|
| ~~`sermon.php`~~ | ~~508~~ | ~~php:S6600~~ | ~~Remove the parentheses from this "echo" call~~ | **FIXED** (pending) |
| ~~`src/Utilities/HelperFunctions.php`~~ | ~~98~~ | ~~php:S131~~ | ~~Add a "case default" clause to this "switch" statement~~ | **FIXED** (pending) |

---

## BUGS (21 total)

### Function Argument Mismatches (php:S930) - 3 issues

| File | Line | Message |
|------|------|---------|
| `sermon.php` | 449 | `sb_display_url` expects 0 arguments, but 1 was provided |
| `src/Admin/Ajax/FilePaginationAjax.php` | 151 | `sb_get_option` expects 1 argument, but 2 were provided |
| `src/Admin/Ajax/SermonPaginationAjax.php` | 58 | `sb_get_option` expects 1 argument, but 2 were provided |

### Replace require with require_once (php:S2003) - 5 issues

| File | Line | Status |
|------|------|--------|
| ~~`src/Admin/Pages/TemplatesPage.php`~~ | ~~63~~ | **FIXED** (pending) |
| ~~`src/Admin/Pages/UninstallPage.php`~~ | ~~57~~ | **FIXED** (pending) |
| ~~`src/Blocks/BlockRegistry.php`~~ | ~~124~~ | **FIXED** (pending) - refactored to cached helper |
| ~~`src/Blocks/BlockRegistry.php`~~ | ~~164~~ | **FIXED** (pending) - refactored to cached helper |
| ~~`src/Frontend/FileDisplay.php`~~ | ~~203~~ | **FIXED** (pending) |

### Missing Table Headers (Web:S5256) - 7 issues (was 8)

| File | Line | Status |
|------|------|--------|
| `src/Admin/Pages/OptionsPage.php` | 352 | |
| `src/Admin/Pages/OptionsPage.php` | 376 | |
| `src/Admin/Pages/PreachersPage.php` | 234 | |
| `src/Admin/Pages/SermonEditorPage.php` | 663 | |
| `src/Admin/Pages/SermonEditorPage.php` | 679 | |
| `src/Admin/Pages/TemplatesPage.php` | 96 | |
| `src/Admin/Pages/UninstallPage.php` | 97 | |
| ~~`src/Frontend/FilterRenderer.php`~~ | ~~578~~ | **FIXED** (ccf1450) |

### Duplicate HTML IDs (Web:S7930) - 3 issues

(Same as Critical section - counted in both BUG and CRITICAL)

### Other Bugs

| File | Line | Rule | Message | Status |
|------|------|------|---------|--------|
| `sermon.php` | 622 | php:S1226 | Introduce a new variable instead of reusing parameter `$content` | |
| ~~`src/Admin/Pages/HelpPage.php`~~ | ~~176~~ | ~~Web:FrameWithoutTitleCheck~~ | ~~Add a "title" attribute to this `<iframe>` tag~~ | **FIXED** (pending) |

---

## VULNERABILITIES (7 total) - ✅ ALL REVIEWED

All are permission safety checks (php:S2612) - chmod operations. **VERIFIED CORRECT** on 2026-02-01:
- Directories use 0755 (WordPress recommended)
- Files use 0644 (WordPress recommended)

| File | Line | Permission | Status |
|------|------|------------|--------|
| ~~`src/Admin/Pages/OptionsPage.php`~~ | ~~180~~ | 0755 (dir) | **REVIEWED** - Correct |
| ~~`src/Admin/Pages/OptionsPage.php`~~ | ~~184~~ | 0755 (dir) | **REVIEWED** - Correct |
| ~~`src/Admin/Pages/PreachersPage.php`~~ | ~~154~~ | 0755 (dir) | **REVIEWED** - Correct |
| ~~`src/Admin/Pages/PreachersPage.php`~~ | ~~166~~ | 0755 (dir) | **REVIEWED** - Correct |
| ~~`src/Admin/Pages/PreachersPage.php`~~ | ~~296~~ | 0755 (dir) | **REVIEWED** - Correct |
| ~~`src/Install/Upgrader.php`~~ | ~~172~~ | 0644 (file) | **REVIEWED** - Correct |
| ~~`src/Install/Upgrader.php`~~ | ~~202~~ | 0755 (dir) | **REVIEWED** - Correct |

---

## Priority Fix Order

### Immediate (Blockers + High-Impact Bugs)

1. **FilePaginationAjaxTest.php:440** - Add assertion to test (BLOCKER)
2. **sermon.php:449** - Fix `sb_display_url` argument mismatch
3. **FilePaginationAjax.php:151** - Fix `sb_get_option` argument mismatch
4. **SermonPaginationAjax.php:58** - Fix `sb_get_option` argument mismatch

### Quick Wins (Easy Fixes)

5. **require → require_once** - 5 files (mechanical change)
6. **echo parentheses** - sermon.php:508 (remove parens)
7. **switch default** - HelperFunctions.php:98 (add default case)
8. **iframe title** - HelpPage.php:176 (add title attribute)

### Medium Effort (HTML/Accessibility)

9. **Duplicate HTML IDs** - FilesPage.php (rename IDs in orphan/trash tables)
10. **Missing table headers** - 8 tables across multiple files

### Larger Refactoring

11. **Cognitive complexity** - 9 functions need splitting
    - SermonEditorPage.php:505 (59 → <15) - largest
    - sermon.php has 4 functions needing refactoring
12. **Duplicate literals** - Create Constants class for 17+ repeated strings

---

## Progress Since Initial Scan

| Metric | Initial | Current | Change |
|--------|---------|---------|--------|
| Total Issues | 1,489 | 214 | -1,275 (85% reduction) |
| Blockers | 4 | 1 | -3 |
| Critical | 59 | 30 | -29 |
| Major | 147 | 140 | -7 |
| Minor | 49 | 43 | -6 |

Major reduction due to:
- Deletion of legacy `sb-includes/` directory
- Refactoring work completed in previous sessions
- UpgraderTest.php blocker fixes

---

## Fixes This Session (Pending Push)

| Commit | File | Issues Fixed |
|--------|------|--------------|
| ccf1450 | `src/Frontend/FilterRenderer.php` | php:S3776 (complexity 28→<15), Web:S5256 (missing th) |
| pending | `tests/Unit/Admin/Ajax/FilePaginationAjaxTest.php` | php:S2699 (missing assertion) - BLOCKER |
| pending | `sermon.php` | php:S6600 (echo parentheses) |
| pending | `src/Utilities/HelperFunctions.php` | php:S131 (missing switch default) |
| pending | `src/Admin/Pages/HelpPage.php` | Web:FrameWithoutTitleCheck (iframe title) |
| pending | `src/Admin/Pages/TemplatesPage.php` | php:S2003 (require → require_once) |
| pending | `src/Admin/Pages/UninstallPage.php` | php:S2003 (require → require_once) |
| pending | `src/Blocks/BlockRegistry.php` | php:S2003 x2 (refactored to cached helper) |
| pending | `src/Frontend/FileDisplay.php` | php:S2003 (require → require_once) |

**Expected reduction after push:** -11 issues (1 blocker, 2 critical, 8 bugs)

---

## Notes

- Line numbers may shift as other agents make changes
- Vulnerability issues (php:S2612) are "security hotspot" style - need manual review, not necessarily bugs
- Duplicate HTML IDs in FilesPage.php are caused by having two tables (main files + orphan/trash) with similar structure
- sermon.php cognitive complexity issues will be addressed during ongoing refactoring work
