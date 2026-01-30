# JavaScript Modernization Plan

**Date:** 2026-01-30
**Status:** Ready for implementation
**Scope:** Replace legacy JavaScript with modern alternatives

## Overview

Replace two legacy JavaScript files (570 lines, 155 SonarQube issues) with modern browser-native and WordPress-bundled alternatives.

## Goals

1. Eliminate all 155 JavaScript SonarQube issues
2. Remove 570 lines of legacy code
3. Use well-maintained, standard solutions
4. Zero new external dependencies

---

## Task 1: Replace 64.js with Native atob()/btoa()

### Current State
- **File:** `sb-includes/64.js` (141 lines)
- **Issues:** 45 SonarQube issues
- **Usage:** 1 location

### Changes Required

#### 1.1 Update admin.php (line 2059)

**Before:**
```javascript
jQuery(".newcode input", this).val(Base64.decode(stuff[i]));
```

**After:**
```javascript
jQuery(".newcode input", this).val(atob(stuff[i]));
```

#### 1.2 Remove script registration from sermon.php (line 167)

**Delete:**
```php
wp_register_script('sb_64', SB_PLUGIN_URL.'/sb-includes/64.js', false, SB_CURRENT_VERSION);
```

#### 1.3 Search for any wp_enqueue_script calls for 'sb_64'

```bash
grep -r "sb_64" . --include="*.php"
```

Remove any enqueue calls found.

#### 1.4 Delete the file

```bash
rm sb-includes/64.js
```

### Verification
- Load admin page that uses embed codes
- Verify embed code field populates correctly
- Check browser console for errors

---

## Task 2: Replace datePicker.js with jQuery UI Datepicker

### Current State
- **File:** `sb-includes/datePicker.js` (429 lines)
- **Issues:** 110 SonarQube issues
- **Usage:** 3 locations

### Changes Required

#### 2.1 Update frontend.php (lines 1115-1117)

**Before:**
```javascript
jQuery.datePicker.setDateFormat('ymd','-');
jQuery('#date').datePicker({startDate:'01/01/1970'});
jQuery('#enddate').datePicker({startDate:'01/01/1970'});
```

**After:**
```javascript
jQuery('#date').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: new Date(1970, 0, 1),
    changeMonth: true,
    changeYear: true
});
jQuery('#enddate').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: new Date(1970, 0, 1),
    changeMonth: true,
    changeYear: true
});
```

#### 2.2 Update admin.php (lines 1974-1975)

**Before:**
```javascript
jQuery.datePicker.setDateFormat('ymd','-');
jQuery('#date').datePicker({startDate:'01/01/1970'});
```

**After:**
```javascript
jQuery('#date').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: new Date(1970, 0, 1),
    changeMonth: true,
    changeYear: true
});
```

#### 2.3 Update sermon.php - Replace script registration (line 168)

**Before:**
```php
wp_register_script('sb_datepicker', SB_PLUGIN_URL.'/sb-includes/datePicker.js', array('jquery'), SB_CURRENT_VERSION);
```

**After:**
```php
// jQuery UI Datepicker is bundled with WordPress - just enqueue it where needed
```

#### 2.4 Add jQuery UI Datepicker enqueue where needed

Find where `sb_datepicker` is enqueued and replace with:
```php
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
```

Or use WordPress bundled style:
```php
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('wp-jquery-ui-dialog'); // Includes basic jQuery UI styling
```

#### 2.5 Delete the file

```bash
rm sb-includes/datePicker.js
```

### Verification
- Load frontend search page with date fields
- Load admin sermon edit page
- Verify datepicker opens on field click
- Verify date format is correct (YYYY-MM-DD)
- Check styling is acceptable

---

## File Changes Summary

| File | Action |
|------|--------|
| `sb-includes/64.js` | DELETE |
| `sb-includes/datePicker.js` | DELETE |
| `sermon.php` | Remove 2 wp_register_script lines, add jQuery UI enqueue |
| `sb-includes/admin.php` | Update Base64.decode → atob, update datePicker → datepicker |
| `sb-includes/frontend.php` | Update datePicker → datepicker |

## Search Commands

Before implementing, search for all usages:

```bash
# Find all 64.js references
grep -r "64\.js\|sb_64\|Base64\." . --include="*.php" --include="*.js"

# Find all datePicker references
grep -r "datePicker\|sb_datepicker" . --include="*.php" --include="*.js"
```

## Testing Checklist

- [ ] Admin: Embed code field works (atob)
- [ ] Admin: Date picker opens and selects dates
- [ ] Frontend: Date filter fields work
- [ ] Frontend: End date field works
- [ ] No JavaScript console errors
- [ ] Date format matches existing behavior (YYYY-MM-DD)

## Rollback

If issues arise, restore from git:
```bash
git checkout HEAD -- sb-includes/64.js sb-includes/datePicker.js
```

## Notes

- jQuery UI Datepicker uses lowercase `.datepicker()` not `.datePicker()`
- Date format string differs: `'yy-mm-dd'` not `'ymd'`
- WordPress bundles jQuery UI, so no new dependencies needed
- `atob()`/`btoa()` supported in all browsers since IE10

---

## Task 3: Add Nonce Verification to Form Processing

### Security Issue
- **Problem:** Processing form data without nonce verification
- **Risk:** CSRF (Cross-Site Request Forgery) attacks
- **SonarQube Rule:** WordPress security best practice

### Required Changes

#### 3.1 Identify all form handlers

Search for form processing that lacks nonce checks:
```bash
# Find POST/REQUEST handlers
grep -r "\$_POST\|\$_REQUEST\|\$_GET" . --include="*.php" | grep -v "nonce"
```

#### 3.2 Add nonce field to forms

For each form, add a nonce field:
```php
wp_nonce_field('sb_action_name', 'sb_nonce');
```

#### 3.3 Verify nonce in handlers

Before processing any form data:
```php
if (!isset($_POST['sb_nonce']) || !wp_verify_nonce($_POST['sb_nonce'], 'sb_action_name')) {
    wp_die(__('Security check failed', 'flavor-flavor'));
}
```

#### 3.4 For AJAX handlers (Priority: ajax.php)

**Step 1:** In `sb-includes/admin.php`, add nonce to JavaScript (near existing wp_localize_script):
```php
wp_localize_script('sb-admin', 'sbAjax', array(
    'nonce' => wp_create_nonce('sb_ajax_nonce')
));
```

**Step 2:** In `sb-includes/ajax.php` line 1, add verification:
```php
<?php
// Verify AJAX nonce before processing any requests
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sb_ajax_nonce')) {
    wp_die(__('Security check failed', 'sermon-browser'), 403);
}
```

**Step 3:** Update all AJAX calls in admin.php JavaScript to include nonce:
```javascript
// Add to each jQuery.post() call
data: { ..., nonce: sbAjax.nonce }
```

### Files Requiring Nonce Verification

| File | Risk | Issue |
|------|------|-------|
| `sb-includes/ajax.php` | **CRITICAL** | No nonce verification. Handles DELETE/UPDATE for preachers, services, series, files via AJAX |
| `sb-includes/uninstall.php` | HIGH | No nonce check (called from admin.php which has check, but direct access possible) |
| `sb-includes/frontend.php` | MEDIUM | Search form filters (read-only but should still verify) |
| `sermon.php` | MEDIUM | Download/show URL handlers at lines 104, 135 |
| `sb-includes/podcast.php` | LOW | Read-only filter for podcast feeds |

### Files WITH Nonce Verification (Reference)

| File | Coverage |
|------|----------|
| `sb-includes/admin.php` | ✓ Options, templates, preachers, sermons, uninstall, clean |

### Verification

- [ ] All forms include nonce fields
- [ ] All POST handlers verify nonce before processing
- [ ] All AJAX handlers use check_ajax_referer()
- [ ] Forms fail gracefully when nonce is invalid
- [ ] No SonarQube warnings about nonce verification

---

## Expected Outcome

| Metric | Before | After |
|--------|--------|-------|
| JS Files | 2 | 0 |
| Lines of Code | 570 | 0 (using built-ins) |
| SonarQube Issues (JS) | 155 | 0 |
| CSRF Vulnerabilities | Multiple | 0 |
| External Dependencies | 0 | 0 |

## Task Summary

| Task | Description | Status |
|------|-------------|--------|
| Task 1 | Replace 64.js with native atob()/btoa() | Pending |
| Task 2 | Replace datePicker.js with jQuery UI Datepicker | Pending |
| Task 3 | Add nonce verification to all form processing | Pending |
