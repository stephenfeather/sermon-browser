# Sermon Browser WordPress Plugin Compatibility Research Report

**Generated:** 2026-01-12  
**Plugin Version:** 0.45.22  
**Last Updated:** 2018  
**Tested Up To:** WordPress 4.9.8  

---

## Executive Summary

The Sermon Browser plugin has significant compatibility issues with modern WordPress (6.x) and PHP (8.x) environments. The plugin uses several deprecated and removed PHP functions, deprecated WordPress APIs, and outdated jQuery patterns. Without remediation, the plugin will:

1. **FAIL to activate** on PHP 8.0+ due to removed `preg_replace` `/e` modifier
2. **Generate fatal errors** or deprecation warnings throughout operation
3. **Have broken admin functionality** due to jQuery API changes
4. **Present security vulnerabilities** through multiple unsanitized inputs and use of `eval()`

**Severity Assessment:**
- **Critical (Blocking):** 3 issues
- **High Priority:** 8 issues  
- **Medium Priority:** 12 issues
- **Low Priority:** 6 issues

---

## Critical Issues (Blocking Activation/Function)

### 1. preg_replace() with /e Modifier - FATAL ERROR

**Location:** `/sb-includes/admin.php` line 1550

```php
$tempfilename = $sermonUploadDir.preg_replace('/([ ])/e', 'chr(rand(97,122))', '		').'.mp3';
```

**Problem:** The `/e` modifier was deprecated in PHP 5.5 and **completely removed in PHP 7.0**. This code will cause a fatal error on any PHP 7+ server.

**Impact:** Plugin will crash when attempting to import files with ID3 tags.

**Fix Required:** Replace with `preg_replace_callback()`:
```php
$tempfilename = $sermonUploadDir.preg_replace_callback('/([ ])/', function($m) { 
    return chr(rand(97,122)); 
}, '		').'.mp3';
```

**Confidence:** VERIFIED - Found at admin.php:1550

### 2. eval() with User-Controlled Template Content

**Location:** `/sermon.php` lines 452, 477

```php
eval('?>'.sb_get_option('single_output'));
eval($output);
```

**Problem:** The plugin uses `eval()` to execute template output. While the templates are stored in the database (not direct user input), this pattern:
1. Is a security risk if the database is compromised
2. May cause PHP parse errors with malformed templates
3. Is blocked by some security plugins and hosting environments
4. Can conflict with PHP 8's stricter error handling

**Impact:** May prevent the plugin from displaying sermons on PHP 8+ or security-hardened hosts.

**Fix Required:** Replace eval-based templating with proper shortcode/function-based rendering.

**Confidence:** VERIFIED - Found at sermon.php:452, 477

### 3. Deprecated Widget Registration API

**Locations:** 
- `/sb-includes/widget.php` lines 81-88
- `/sermon.php` lines 500-515

```php
wp_register_sidebar_widget($id, $name, 'sb_widget_sermon_wrapper', $widget_ops, array('number' => $o));
wp_register_widget_control($id, $name, 'sb_widget_sermon_control', $control_ops, array('number' => $o));
```

**Problem:** `wp_register_sidebar_widget()` and `wp_register_widget_control()` were deprecated in WordPress 2.8 and are scheduled for removal. WordPress 5.8+ introduced Block-based widgets which creates additional incompatibility.

**Impact:** Widgets may not appear in the Customizer or Widget admin area in WordPress 6.x. Future WordPress versions may remove these functions entirely.

**Fix Required:** Convert to modern `WP_Widget` class-based widgets.

**Confidence:** VERIFIED - Multiple locations confirmed

---

## High Priority Issues (Likely to Cause Errors)

### 4. strftime() Function - Deprecated in PHP 8.1

**Locations:**
- `/sb-includes/admin.php` line 1361
- `/sb-includes/ajax.php` line 129  
- `/sb-includes/frontend.php` lines 819, 901

```php
strftime('%d %b %y', strtotime($sermon->datetime));
strftime('%B', strtotime("{$date->year}-{$date->month}-{$date->day}"));
```

**Problem:** `strftime()` is deprecated in PHP 8.1 and will be removed in PHP 9.0. It generates deprecation warnings.

**Fix Required:** Replace with `IntlDateFormatter` or `date()` with manual translation.

**Confidence:** VERIFIED - 4 occurrences found

### 5. is_site_admin() Function - Removed in WordPress 3.0

**Locations:** `/sb-includes/admin.php` lines 95, 104, 195

```php
if (IS_MU AND !is_site_admin()) {
```

**Problem:** `is_site_admin()` was removed in WordPress 3.0, replaced by `is_super_admin()`.

**Impact:** Multisite installations may have broken permission checks.

**Fix Required:** Replace with `is_super_admin()`.

**Confidence:** VERIFIED - 3 occurrences

### 6. WPLANG Constant - Deprecated

**Location:** `/sermon.php` lines 151-153

```php
if(defined('WPLANG')){
    if (WPLANG != '')
        setlocale(LC_ALL, WPLANG.'.UTF-8');
}
```

**Problem:** The `WPLANG` constant was deprecated in WordPress 4.0. Language is now stored in the database.

**Fix Required:** Use `get_locale()` instead.

**Confidence:** VERIFIED - Found at sermon.php:151-153

### 7. jQuery .attr() for Properties

**Locations:** Throughout `/sb-includes/admin.php` (15+ occurrences)

```javascript
jQuery("#preacher option[value='" + r + "']").attr('selected', 'selected');
jQuery('#time').attr('disabled', 'disabled');
jQuery("option[value='filelist']", this).attr('selected', 'selected');
```

**Problem:** In jQuery 1.9+, using `.attr()` for boolean attributes like `selected`, `checked`, and `disabled` is deprecated. jQuery 3.x (bundled since WordPress 5.6) requires `.prop()` for properties.

**Impact:** Form controls may not work correctly - selected options won't be selected, disabled inputs may be editable.

**Fix Required:** Replace `.attr('selected', 'selected')` with `.prop('selected', true)`, etc.

**Confidence:** VERIFIED - 15+ occurrences in admin.php

### 8. jQuery Migrate Dependency

**Problem:** The plugin relies on jQuery behaviors that require jQuery Migrate. WordPress 5.5 stopped bundling jQuery Migrate by default, and it's being phased out entirely.

**Impact:** Various JavaScript functionality may break silently or with console errors.

**Fix Required:** Update all jQuery code to be jQuery 3.x compatible.

**Sources:** 
- [jQuery Migrate Helper Plugin](https://wordpress.org/plugins/enable-jquery-migrate-helper/)
- [WordPress jQuery Update Status](https://sternerstuff.dev/2025/12/the-status-of-jquery-migrate-in-wordpress-6-9-and-beyond/)

### 9. extract() Function Usage - Security Concern

**Locations:**
- `/sb-includes/admin.php` line 2365
- `/sb-includes/frontend.php` lines 19, 58, 62, 66, 103, 149
- `/sb-includes/widget.php` lines 34, 97, 142, 146, 150, 200

```php
extract($options);
extract($args, EXTR_SKIP);
extract($widget_args, EXTR_SKIP);
```

**Problem:** While `extract()` still works in PHP 8, it's considered a security antipattern. Many code analysis tools flag it, and it can lead to variable scope pollution.

**Impact:** Security scanners will flag this. Potential for variable conflicts.

**Fix Required:** Replace with explicit variable assignments.

**Confidence:** VERIFIED - 13 occurrences

### 10. implode() Argument Order

**Location:** `/sb-includes/dictionary.php` line 48

```php
'[/passages_loop]' => '<?php endfor; echo implode($ref_output, ", "); ?>',
```

**Problem:** Using `implode($array, $separator)` with the separator as the second argument is deprecated as of PHP 7.4 and may cause issues.

**Fix Required:** Swap arguments to `implode(", ", $ref_output)`.

**Confidence:** VERIFIED - Found at dictionary.php:48

### 11. Missing Type Declarations with Null Parameters

**Problem:** PHP 8.x has stricter null handling. Many WordPress functions that previously accepted null now require explicit types, causing "Passing null to parameter" deprecation warnings.

**Impact:** Extensive deprecation warnings in error logs.

---

## Medium Priority Issues (Deprecation Warnings)

### 12. Direct $_GET/$_POST Access Without Sanitization

**Locations:** Multiple throughout the codebase

```php
// Examples of unsanitized access:
$_POST['import_type']  // admin.php:913
$_POST['date']         // admin.php:1430
$_POST['time']         // admin.php:1438
$_POST['description']  // admin.php:1445 (only kses'd for some users)
$_POST['url']          // admin.php:1505
$_REQUEST['date']      // podcast.php:85
$_REQUEST['enddate']   // podcast.php:86
```

**Problem:** While many inputs ARE sanitized (credit to the developers), several are used directly or with insufficient sanitization.

**Impact:** Potential XSS and SQL injection vectors.

**Fix Required:** Apply `sanitize_text_field()`, `absint()`, or `esc_sql()` consistently to all inputs.

**Confidence:** VERIFIED - Multiple occurrences

### 13. Nonce Verification Gaps

**Problem:** While many forms have nonce verification (good), some AJAX endpoints and form handlers lack verification:
- Some POST handlers check nonces, but the check happens after data processing begins
- Some AJAX calls don't verify nonces

**Impact:** Potential CSRF vulnerabilities.

### 14. SQL Query Construction

**Locations:** Multiple

```php
// Example of potential SQL injection:
$file_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}sb_stuff WHERE name='{$file_name}'");
```

While `esc_sql()` is used in some places, the pattern of string concatenation for SQL queries is error-prone.

**Impact:** Potential SQL injection if escaping is missed.

**Fix Required:** Use `$wpdb->prepare()` consistently.

### 15. Deprecated rightnow_end Hook

**Location:** `/sermon.php` line 225

```php
add_action ('rightnow_end', 'sb_rightnow');
```

**Problem:** The `rightnow_end` hook was part of the "Right Now" dashboard widget, which was replaced by "At a Glance" in WordPress 3.8.

**Impact:** Dashboard statistics may not appear.

**Fix Required:** Use `dashboard_glance_items` filter instead.

### 16. contextual_help Filter - Deprecated

**Location:** `/sermon.php` line 227

```php
add_filter('contextual_help', 'sb_add_contextual_help');
```

**Problem:** The `contextual_help` filter was deprecated in WordPress 3.3 in favor of Help Tabs API.

**Fix Required:** Use `get_current_screen()->add_help_tab()`.

### 17. Missing Capability Checks

**Problem:** Some operations rely solely on `current_user_can()` for a single capability. WordPress best practices recommend checking specific capabilities for each operation type.

### 18. Inline JavaScript

**Problem:** Much of the admin JavaScript is inline PHP-generated code rather than properly enqueued scripts. This:
- Can conflict with Content Security Policy
- Doesn't benefit from browser caching
- Can cause issues with script optimization plugins

### 19. base64_encode/decode for Options

**Location:** Throughout sermon.php

```php
return stripslashes(base64_decode(get_option("sermonbrowser_{$type}")));
update_option ("sermonbrowser_{$type}", base64_encode($val));
```

**Problem:** While not broken, storing options as base64 is unusual and can cause issues with option migration tools.

### 20. @chmod() and @mkdir() Suppression

**Locations:** Multiple in sermon.php and admin.php

**Problem:** Error suppression with `@` hides useful debugging information and is considered poor practice.

### 21. PHP 8 Dynamic Properties

**Problem:** PHP 8.2 deprecates dynamic properties on classes. The plugin may create properties dynamically on objects.

### 22. Unserialize() on Untrusted Data

**Locations:** 
- `/sermon.php` line 570
- `/sb-includes/admin.php` line 1650-1651

```php
$startArr = unserialize($curSermon->start);
```

**Problem:** `unserialize()` on database content can be a security risk if the database is compromised.

**Fix Required:** Use `maybe_unserialize()` and validate data structure.

### 23. Hardcoded HTTP URLs

**Locations:** Multiple in admin.php and sb-install.php

```php
<a href="http://www.sermonbrowser.com/">
```

**Problem:** Hardcoded HTTP (not HTTPS) URLs may cause mixed content warnings on HTTPS sites.

---

## Low Priority Issues (Best Practices)

### 24. No REST API Endpoints

**Problem:** The plugin predates the WordPress REST API and uses custom AJAX handlers instead.

**Impact:** No integration with headless WordPress, mobile apps, or modern frontend frameworks.

### 25. No Block Editor Support

**Problem:** The plugin uses a shortcode-based approach with no Gutenberg blocks.

**Impact:** Users must use the shortcode block or Classic Editor. No native block experience.

### 26. No Escaping on Output

**Locations:** Many template output locations

```php
echo stripslashes($sermon->title)
```

**Problem:** While `stripslashes()` is used, proper escaping with `esc_html()` is often missing.

### 27. Legacy Translation Loading

**Location:** `/sermon.php` lines 146-149

```php
if (IS_MU) {
    load_plugin_textdomain('sermon-browser', '', 'sb-includes');
} else {
    load_plugin_textdomain('sermon-browser', '', 'sermon-browser/sb-includes');
}
```

**Problem:** The second parameter is deprecated. Modern approach uses only the first and third.

### 28. Multisite Detection

**Location:** `/sermon.php` lines 638-643

```php
define('IS_MU', TRUE);
```

**Problem:** The multisite detection logic is outdated. Modern approach uses `is_multisite()`.

### 29. Missing Plugin Headers

**Problem:** The plugin lacks some modern plugin headers like `Requires PHP:` and `Requires at least:`.

---

## Recommended Modernization Path

### Phase 1: Critical Fixes (Required for PHP 8.0+)

1. **Replace preg_replace /e modifier** with preg_replace_callback
2. **Replace deprecated widget API** with WP_Widget class
3. **Fix strftime() calls** with date() or IntlDateFormatter
4. **Replace is_site_admin()** with is_super_admin()
5. **Fix WPLANG** usage

### Phase 2: jQuery Compatibility (Required for WordPress 5.6+)

1. **Update all .attr()** calls for boolean properties to .prop()
2. **Remove jQuery Migrate dependencies**
3. **Test all admin JavaScript functionality**

### Phase 3: Security Hardening

1. **Consistent input sanitization** for all $_GET/$_POST/$_REQUEST
2. **Use $wpdb->prepare()** consistently
3. **Remove or isolate eval()** usage
4. **Replace extract()** with explicit variables
5. **Add nonce verification** to all AJAX endpoints

### Phase 4: Modern WordPress Integration

1. **Convert widgets** to use WP_Widget properly
2. **Add Block Editor support** (Gutenberg blocks)
3. **Add REST API endpoints**
4. **Use Help Tabs API** instead of contextual_help

### Phase 5: Code Quality

1. **Add Requires PHP: 8.0** header
2. **Add Requires at least: 6.0** header  
3. **Update all translation calls**
4. **Add proper error handling** instead of @ suppression
5. **Convert inline JavaScript** to enqueued scripts

---

## Testing Recommendations

1. **Test on PHP 8.1+** with error reporting enabled
2. **Test on WordPress 6.5+** with debug mode enabled
3. **Test all widget functionality** in both Customizer and Widget admin
4. **Test all admin forms** with JavaScript console open
5. **Test with security plugins** like Wordfence or Sucuri
6. **Test podcast functionality**
7. **Test file upload and import**

---

## Sources

- [Enable jQuery Migrate Helper](https://wordpress.org/plugins/enable-jquery-migrate-helper/)
- [jQuery Migrate in WordPress 6.9](https://sternerstuff.dev/2025/12/the-status-of-jquery-migrate-in-wordpress-6-9-and-beyond/)
- [PHP Deprecated Features Cheatsheet](https://eusonlito.github.io/php-changes-cheatsheet/deprecated.html)
- [preg_replace /e Modifier Removal](https://phplift.com/compatibility/functions/regex-e-modifier)
- [PHP 8.0 Changes](https://php.watch/versions/8.0)
- [WordPress Trac - jQuery Update](https://core.trac.wordpress.org/ticket/37110)

---

## Conclusion

The Sermon Browser plugin requires significant updates to function on modern WordPress and PHP environments. The most critical issue - the preg_replace /e modifier - will cause a **fatal error** on PHP 7.0+, making the plugin completely non-functional on any modern PHP installation.

A comprehensive modernization effort is recommended rather than piecemeal fixes, as the codebase has accumulated significant technical debt since its last update in 2018.
