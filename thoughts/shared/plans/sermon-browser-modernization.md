# Implementation Plan: Sermon Browser WordPress Plugin Modernization

**Generated:** 2026-01-12
**Plugin Version:** 0.45.22
**Target Compatibility:** WordPress 6.x, PHP 8.x

---

## Goal

Modernize the Sermon Browser plugin to run without errors on WordPress 6.x with PHP 8.2+. The plugin currently uses deprecated/removed PHP functions and WordPress APIs that cause fatal errors or deprecation warnings.

**Primary objective:** Get the plugin functional on modern environments while incrementally improving code organization.

**Secondary objective:** Refactor procedural include files into classes where practical, improving maintainability and testability.

**Non-goals for this plan:**
- Adding new features (REST API, Block Editor)
- Complete security audit
- Complete OOP rewrite (gradual refactoring only)
- Multisite-specific testing (deferred - fixes are straightforward replacements)

---

## Branch Strategy

- **main** - Release branch, stable code only
- **develop** - Working branch for all modernization changes

All work happens on `develop`. Merge to `main` only after Phase 6 validation passes.

> **Note:** Plugin is currently non-functional on modern PHP/WordPress, so partial releases on develop are acceptable.

---

## Class-Based Refactoring Strategy

As we touch code during modernization, refactor procedural includes into classes where practical.

### Refactoring Principles

1. **Refactor as you go** - Don't create a separate "refactoring phase." Convert to classes when fixing code in that file.
2. **One class per concern** - Each class should have a single responsibility.
3. **Preserve backward compatibility** - Keep existing function names as wrappers that delegate to class methods.
4. **Namespace appropriately** - Use `SermonBrowser\` namespace for new classes.
5. **Autoloading** - Use Composer PSR-4 autoloading for new classes.

### Target Class Structure

```
sb-includes/
├── classes/
│   ├── Admin.php           # From admin.php - admin UI and handlers
│   ├── Ajax.php            # From ajax.php - AJAX endpoints
│   ├── Dictionary.php      # From dictionary.php - template tags
│   ├── Frontend.php        # From frontend.php - public display
│   ├── Installer.php       # From sb-install.php - installation
│   ├── Podcast.php         # From podcast.php - feed generation
│   ├── Upgrader.php        # From upgrade.php - migrations
│   └── Widgets/
│       ├── SermonsWidget.php
│       ├── TagsWidget.php
│       └── PopularWidget.php
```

### Refactoring Priority by File

| File | Size | Refactor Priority | Notes |
|------|------|-------------------|-------|
| admin.php | 128k | High | Break into Admin + AdminAjax classes |
| frontend.php | 49k | Medium | Frontend rendering class |
| widget.php | 12k | High (Phase 3) | Already converting to WP_Widget |
| sb-install.php | 13k | Low | Touch only if fixing bugs |
| upgrade.php | 8.3k | Low | Touch only if fixing bugs |
| ajax.php | 9.4k | Medium | Clean separation possible |
| dictionary.php | 7.5k | Low | Template tags, works as-is |
| podcast.php | 6.7k | Medium | Good candidate for class |

### Backward Compatibility Pattern

When converting a function to a class method:

```php
// Old function (keep for backward compatibility)
function sb_some_function($args) {
    return SermonBrowser\Admin::instance()->some_function($args);
}

// New class method
namespace SermonBrowser;

class Admin {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function some_function($args) {
        // Actual implementation
    }
}
```

### Composer Autoloading

Add to `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "SermonBrowser\\": "sb-includes/classes/"
        }
    }
}
```

### When NOT to Refactor

- Simple utility functions that don't share state
- Code that works fine and isn't being modified
- If refactoring would delay critical fixes

---

## Testing Strategy (TDD)

**Target:** ~90% test coverage for modified code by project completion.

**Approach:** Write tests for code we touch. Each phase adds tests before implementation.

### Test Infrastructure Setup (Phase 0.5)

Before Phase 1 implementation:

1. **Add PHPUnit configuration**
   - Create `phpunit.xml.dist`
   - Create `tests/bootstrap.php` with WordPress test library
   - Create `tests/` directory structure

2. **Docker test environment**
   - Add test database container
   - Configure `WP_TESTS_DIR` environment variable

3. **Directory structure:**
   ```
   tests/
   ├── bootstrap.php
   ├── Unit/
   │   ├── AdminTest.php
   │   ├── DictionaryTest.php
   │   ├── FrontendTest.php
   │   └── WidgetTest.php
   └── Integration/
       ├── PluginActivationTest.php
       ├── SermonCRUDTest.php
       └── WidgetMigrationTest.php
   ```

### TDD Workflow Per Phase

For each code change:
1. **RED:** Write failing test that exercises the code to be modified
2. **GREEN:** Implement the fix, test passes
3. **REFACTOR:** Clean up if needed, tests still pass

### Test Coverage Targets by Phase

| Phase | Focus | Coverage Target |
|-------|-------|-----------------|
| 1 | Critical PHP fixes | Tests for preg_replace, locale, implode functions |
| 2 | Deprecated functions | Tests for date formatting, hooks |
| 3 | Widgets | Tests for widget rendering, settings migration |
| 4 | jQuery | Manual browser testing (JS not unit-testable without setup) |
| 5 | Security/Quality | Tests for eval wrapper, extract replacements |
| 6 | Validation | Integration tests for full workflows |

---

## Success Criteria

1. Plugin activates without fatal errors on PHP 8.2
2. Plugin activates without fatal errors on WordPress 6.4
3. All admin pages load without PHP errors
4. Sermons can be viewed on frontend without errors
5. Widgets display in widget admin area
6. No deprecation warnings in PHP error log (target: reduce 95%+)
7. JavaScript admin functionality works (sermon creation/editing)

---

## Testing Environment

Docker environment already configured:
```bash
cd /Users/stephenfeather/Development/sermon-browser
docker-compose up -d

# Access points:
# WordPress: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

Test after each phase by:
1. Activating the plugin
2. Creating a test sermon
3. Viewing the sermon on frontend
4. Checking error log: `docker-compose logs wordpress`

---

## Phase 1: Critical PHP Fixes (Blocks Activation)

**Priority:** CRITICAL - Must complete first
**Estimated effort:** 2-3 hours
**Risk:** Low - direct replacements with clear patterns

### 1.1 Replace preg_replace /e modifier

**File:** `/sb-includes/admin.php` line 1550

**Current (FATAL on PHP 7+):**
```php
$tempfilename = $sermonUploadDir.preg_replace('/([ ])/e', 'chr(rand(97,122))', '		').'.mp3';
```

**Fix:**
```php
$tempfilename = $sermonUploadDir.preg_replace_callback('/([ ])/', function($m) {
    return chr(rand(97,122));
}, '		').'.mp3';
```

**Verification:** Try to import a sermon with ID3 tags from a remote URL.

### 1.2 Replace is_site_admin() calls

**File:** `/sb-includes/admin.php` lines 95, 104, 195

**Current:**
```php
if (IS_MU AND !is_site_admin()) {
```

**Fix:**
```php
if (IS_MU AND !is_super_admin()) {
```

**Verification:** Test on multisite installation.

### 1.3 Fix WPLANG usage

**File:** `/sermon.php` lines 151-154

**Current:**
```php
if(defined('WPLANG')){
    if (WPLANG != '')
        setlocale(LC_ALL, WPLANG.'.UTF-8');
}
```

**Fix:**
```php
$locale = get_locale();
if (!empty($locale)) {
    setlocale(LC_ALL, $locale . '.UTF-8');
}
```

**Verification:** Check frontend date formatting.

### 1.4 Fix implode() argument order

**File:** `/sb-includes/dictionary.php` line 48

**Current:**
```php
'[/passages_loop]' => '<?php endfor; echo implode($ref_output, ", "); ?>',
```

**Fix:**
```php
'[/passages_loop]' => '<?php endfor; echo implode(", ", $ref_output); ?>',
```

**Verification:** View a sermon with multiple passages.

### Phase 1 Test Checkpoint

```bash
# Start fresh containers
docker-compose down -v
docker-compose up -d

# Wait for WordPress to initialize, then:
# 1. Install WordPress at localhost:8080
# 2. Activate Sermon Browser
# 3. Expected: No fatal errors, plugin appears in menu
```

---

## Phase 2: Deprecated Function Fixes

**Priority:** HIGH - Causes deprecation warnings
**Estimated effort:** 2-3 hours
**Risk:** Low to Medium

### 2.1 Replace strftime() calls

**Files and locations:**
- `/sb-includes/admin.php` line 1361
- `/sb-includes/ajax.php` line 129
- `/sb-includes/frontend.php` lines 819, 901

**Pattern - replace:**
```php
strftime('%B', strtotime($datestring))
```

**With:**
```php
wp_date('F', strtotime($datestring))
```

**Full format mapping:**
| strftime | wp_date/date | Meaning |
|----------|--------------|---------|
| %B | F | Full month name |
| %d | d | Day (01-31) |
| %b | M | Short month name |
| %y | y | Two-digit year |

**Note:** `wp_date()` handles localization automatically.

### 2.2 Replace deprecated WordPress hooks

**File:** `/sermon.php` line 225

**Current:**
```php
add_action ('rightnow_end', 'sb_rightnow');
```

**Fix:**
```php
add_filter('dashboard_glance_items', 'sb_dashboard_glance');

// Update the function to return an array item instead of echoing
```

**File:** `/sermon.php` line 227

**Current:**
```php
add_filter('contextual_help', 'sb_add_contextual_help');
```

**Fix:** Convert to Help Tabs API (can be deferred if complex).

### Phase 2 Test Checkpoint

```bash
# After changes:
# 1. Check WordPress dashboard - sermon count should appear
# 2. Check error log for strftime warnings: docker-compose logs wordpress | grep -i deprecated
# 3. Create/edit a sermon - dates should display correctly
```

---

## Phase 3: Widget Modernization

**Priority:** HIGH - Widgets don't appear in modern WordPress
**Estimated effort:** 4-6 hours
**Risk:** Medium - requires API pattern change

### 3.1 Convert to WP_Widget class

**Files to modify:**
- `/sermon.php` (widget init function)
- `/sb-includes/widget.php` (main widget code)

**Current pattern (deprecated):**
```php
wp_register_sidebar_widget($id, $name, 'sb_widget_sermon_wrapper', $widget_ops);
wp_register_widget_control($id, $name, 'sb_widget_sermon_control', $control_ops);
```

**New pattern:**
```php
class SB_Widget_Sermons extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'sb_sermons',
            __('Sermons', 'sermon-browser'),
            array('description' => __('Display a list of recent sermons.', 'sermon-browser'))
        );
    }

    public function widget($args, $instance) {
        // Move code from sb_widget_sermon() here
    }

    public function form($instance) {
        // Move code from sb_widget_sermon_control() here
    }

    public function update($new_instance, $old_instance) {
        // Sanitize and save
    }
}

// In widgets_init:
register_widget('SB_Widget_Sermons');
```

**Widgets to convert:**
1. Sermons widget (main)
2. Tags widget
3. Most popular widget

### 3.2 Widget Settings Migration Routine

**IMPORTANT:** Converting to WP_Widget changes how settings are stored. Add migration to preserve existing user configurations.

**Migration logic (add to plugin activation or admin_init):**

```php
function sb_migrate_widget_settings() {
    // Check if migration already done
    if (get_option('sb_widget_migration_complete')) {
        return;
    }

    // Old format: sb_get_option('sermons_widget_options')
    // New format: get_option('widget_sb_sermons')

    $old_sermon_opts = sb_get_option('sermons_widget_options');
    if ($old_sermon_opts && !get_option('widget_sb_sermons')) {
        $new_opts = array(
            2 => array(  // Widget instance 2 (1 is reserved)
                'title' => $old_sermon_opts['title'] ?? __('Sermons', 'sermon-browser'),
                'limit' => $old_sermon_opts['limit'] ?? 5,
                'preacher' => $old_sermon_opts['preacher'] ?? 0,
                'service' => $old_sermon_opts['service'] ?? 0,
                'series' => $old_sermon_opts['series'] ?? 0,
            ),
            '_multiwidget' => 1
        );
        update_option('widget_sb_sermons', $new_opts);
    }

    // Repeat for tags widget
    $old_tags_opts = sb_get_option('tags_widget_options');
    if ($old_tags_opts && !get_option('widget_sb_tags')) {
        $new_opts = array(
            2 => array(
                'title' => $old_tags_opts['title'] ?? __('Sermon Tags', 'sermon-browser'),
                // ... map other options
            ),
            '_multiwidget' => 1
        );
        update_option('widget_sb_tags', $new_opts);
    }

    // Repeat for popular widget
    $old_popular_opts = sb_get_option('popular_widget_options');
    if ($old_popular_opts && !get_option('widget_sb_popular')) {
        $new_opts = array(
            2 => array(
                'title' => $old_popular_opts['title'] ?? __('Popular Sermons', 'sermon-browser'),
                'limit' => $old_popular_opts['limit'] ?? 5,
            ),
            '_multiwidget' => 1
        );
        update_option('widget_sb_popular', $new_opts);
    }

    update_option('sb_widget_migration_complete', true);
}
add_action('admin_init', 'sb_migrate_widget_settings');
```

**Test requirement:** Write `WidgetMigrationTest.php` to verify old settings are preserved after upgrade.

### 3.3 Replace extract() calls in widget code

**Files:** `/sb-includes/widget.php` lines 34, 97, 142, 146, 150, 200

**Pattern - replace:**
```php
extract($args, EXTR_SKIP);
echo $before_widget;
```

**With:**
```php
$before_widget = $args['before_widget'] ?? '';
$after_widget = $args['after_widget'] ?? '';
$before_title = $args['before_title'] ?? '';
$after_title = $args['after_title'] ?? '';
echo $before_widget;
```

### Phase 3 Test Checkpoint

```bash
# 1. Go to Appearance > Widgets
# 2. Sermon widgets should appear in available widgets
# 3. Add widget to sidebar
# 4. View frontend - widget should display
```

---

## Phase 4: jQuery Compatibility

**Priority:** HIGH - Breaks admin functionality
**Estimated effort:** 3-4 hours
**Risk:** Medium - may need testing across browsers

### 4.1 Replace .attr() for boolean properties

**File:** `/sb-includes/admin.php` (15+ occurrences)

**Pattern - replace:**
```javascript
.attr('selected', 'selected')
.attr('disabled', 'disabled')
.attr('checked', 'checked')
```

**With:**
```javascript
.prop('selected', true)
.prop('disabled', true)
.prop('checked', true)
```

**And replace:**
```javascript
.removeAttr('disabled')
.removeAttr('selected')
```

**With:**
```javascript
.prop('disabled', false)
.prop('selected', false)
```

### 4.2 Update jQuery ready syntax

**Pattern - replace:**
```javascript
jQuery(document).ready(function($) {
```

**With (if not already):**
```javascript
jQuery(function($) {
```

### Phase 4 Test Checkpoint

```bash
# 1. Create a new sermon
# 2. Select preacher, series, service from dropdowns
# 3. Upload/attach files
# 4. Edit existing sermon
# 5. Check browser console for jQuery errors (F12 > Console)
```

---

## Phase 5: Security and Quality Improvements

**Priority:** MEDIUM - Improves stability
**Estimated effort:** 4-6 hours
**Risk:** Low to Medium

### 5.1 Address eval() in templates (risk mitigation)

**File:** `/sermon.php` lines 452, 477

**Current (risky):**
```php
eval('?>'.sb_get_option('single_output'));
```

**Mitigation options:**
1. **Quick fix:** Wrap in try/catch to prevent fatal errors
2. **Better fix:** Replace with include of temp file (allows opcode caching)
3. **Best fix:** Convert to proper shortcode-based rendering (significant effort)

**Recommended for this phase:** Option 1 (try/catch wrapper)

```php
try {
    eval('?>' . sb_get_option('single_output'));
} catch (ParseError $e) {
    echo '<div class="sermon-browser-error">' .
         esc_html__('Template error', 'sermon-browser') . '</div>';
    if (WP_DEBUG) {
        echo '<!-- Template parse error: ' . esc_html($e->getMessage()) . ' -->';
    }
}
```

### 5.2 Replace remaining extract() calls

**Files:** `/sb-includes/frontend.php`, `/sb-includes/admin.php`

Replace with explicit variable assignments.

### 5.3 Update plugin headers

**File:** `/sermon.php` header section

**Add:**
```php
Requires at least: 6.0
Requires PHP: 8.0
```

### Phase 5 Test Checkpoint

```bash
# 1. Test with WP_DEBUG enabled
# 2. Check that template errors don't crash the site
# 3. Run through all major functionality
```

---

## Phase 6: Final Validation

**Priority:** Required before release
**Estimated effort:** 2-3 hours

### 6.1 Full functionality test

Checklist:
- [ ] Plugin activates cleanly
- [ ] Add new sermon with all fields
- [ ] Upload MP3 file
- [ ] Import ID3 tags from file
- [ ] Add external URL
- [ ] Create preacher with image
- [ ] Create series
- [ ] Create service with default time
- [ ] View sermon on frontend
- [ ] Test search/filter
- [ ] Test podcast feed
- [ ] Test widgets
- [ ] Test file download (forced)
- [ ] Test file show (redirect)
- [ ] Delete sermon
- [ ] Uninstall and reinstall

### 6.2 Error log verification

```bash
# Check for remaining PHP errors/warnings
docker-compose logs wordpress 2>&1 | grep -iE "(error|warning|deprecated|notice)" | grep sermon
```

### 6.3 Update readme.txt

- Update "Tested up to" version
- Add changelog entry
- Update "Requires PHP" if adding header

---

## Risks and Mitigations

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| eval() changes break existing templates | High | Medium | Keep backward compatible, add fallback |
| Widget conversion breaks existing widgets | Medium | Medium | Migrate widget settings during upgrade |
| jQuery changes break some functionality | Medium | Low | Test in multiple browsers |
| Missed deprecations cause warnings | Low | Medium | Run with WP_DEBUG, check logs |
| Database schema incompatibility | High | Low | No schema changes in this plan |

---

## Files Modified Summary

| File | Phase | Changes |
|------|-------|---------|
| sermon.php | 1, 2, 5 | WPLANG, hooks, eval safety, headers |
| sb-includes/admin.php | 1, 2, 4 | preg_replace, is_site_admin, strftime, jQuery |
| sb-includes/frontend.php | 2, 5 | strftime, extract |
| sb-includes/widget.php | 3 | WP_Widget conversion |
| sb-includes/dictionary.php | 1 | implode order |
| sb-includes/ajax.php | 2 | strftime |

---

## Out of Scope (Future Work)

These items are intentionally not included in this plan:

1. **REST API endpoints** - Would require significant new code
2. **Block Editor (Gutenberg) support** - Major feature addition
3. **Full security audit** - Separate effort needed
4. **Modern JavaScript (ES6+)** - Would require build tooling
5. **Complete PSR-12 compliance** - Gradual improvement only as we touch files
6. **Multisite-specific testing** - Deferred; fixes are straightforward 1:1 replacements
7. **Complete OOP rewrite** - Gradual class refactoring as we touch code (see Class-Based Refactoring Strategy)

---

## Pre-Mortem Decisions (2026-01-12)

### Accepted Risks

1. **eval() template system remains** - Existing behavior, not introduced by us. Adding try/catch for stability. Security model assumes trusted admins (standard WordPress assumption).

2. **No multisite test environment** - The `is_site_admin()` → `is_super_admin()` replacement is a documented 1:1 equivalent. Low risk of issues.

### Mitigations Added

1. **TDD Approach** - ~90% coverage target for modified code. Write tests before implementing fixes. Added Phase 0.5 for test infrastructure setup.

2. **Widget Migration Routine** - Added `sb_migrate_widget_settings()` in Phase 3.2 to preserve existing widget configurations when converting to WP_Widget API.

3. **Branch Strategy** - Work on `develop`, merge to `main` only after full validation. Partial releases acceptable since plugin is currently non-functional.

---

## Implementation Order

Execute phases in order. Each phase builds on the previous:

```
Phase 1 (Critical)
    |
    v
Phase 2 (Deprecated Functions)
    |
    v
Phase 3 (Widgets)
    |
    v
Phase 4 (jQuery)
    |
    v
Phase 5 (Security/Quality)
    |
    v
Phase 6 (Validation)
```

Do not skip Phase 1 - the plugin will not activate without these fixes.

---

## Version Strategy

Recommend bumping to **0.46.0** to indicate significant compatibility update without implying major feature changes.
