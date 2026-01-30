# Sermon Browser Comprehensive Refactor Plan

**Generated:** 2026-01-29
**Plugin Version:** 0.5.1-dev
**Target WordPress:** 6.0+
**Target PHP:** 8.0+

## Workflow Requirements

- **All work must be done in git worktrees**, not the main checkout
- Create feature branches in worktrees to keep main checkout clean

---

## Executive Summary

This plan outlines a six-phase modernization of the Sermon Browser WordPress plugin, transforming a 15+ year old codebase into a maintainable, testable, and modern architecture. The existing Repository layer in `src/` provides a foundation for Phase 1.

---

## Phase 1: Integrate Repository Layer

### Goals
- Wire the existing PSR-4 Repository classes into the legacy procedural code
- Establish a Service Container pattern for dependency injection
- Create adapter functions to bridge legacy code with repositories
- Enable gradual migration without breaking changes

### Current State

**Repository classes exist (VERIFIED):**
```
src/
  Contracts/
    RepositoryInterface.php          # Standard CRUD contract
  Repositories/
    AbstractRepository.php           # Base class with wpdb integration
    SermonRepository.php             # 344 lines, full CRUD + relations
    PreacherRepository.php           # 117 lines, includes sermon counts
    SeriesRepository.php             # 132 lines, with page linking
    ServiceRepository.php            # 106 lines, time-based queries
    FileRepository.php               # 192 lines, download stats
    TagRepository.php                # 304 lines, pivot table support
```

**Legacy code uses raw $wpdb queries in:**
- `sermon.php` - 32 direct `$wpdb->` calls
- `sb-includes/admin.php` - 80+ direct `$wpdb->` calls
- `sb-includes/frontend.php` - 15+ direct `$wpdb->` calls
- `sb-includes/ajax.php` - AJAX handlers with raw queries

**Composer autoload configured (VERIFIED):**
```json
"autoload": {
    "psr-4": {
        "SermonBrowser\\": "src/"
    }
}
```

### Target State

```
src/
  Contracts/
    RepositoryInterface.php
    ServiceContainerInterface.php    # NEW
  Repositories/
    [existing repositories]
  Services/
    Container.php                    # NEW - DI container
    SermonService.php                # NEW - Business logic layer
    PreacherService.php              # NEW
  Adapters/
    LegacyAdapter.php                # NEW - Bridge functions
```

**Usage pattern:**
```php
// Legacy code (before):
$sermon = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sb_sermons WHERE id = $id");

// Bridge phase (adapter):
$sermon = sb_repo_sermon()->find($id);

// Final state (DI):
$sermon = $this->sermonRepository->find($id);
```

### Decisions (CONFIRMED)

1. **Service Container**: ✅ Simple singleton pattern
   - Global accessor `sb_container()`
   - Lazy instantiation of repositories

2. **Bridge Pattern**: ✅ Static facades for readability
   ```php
   Sermon::find($id);           // Instead of sb_repo_sermon()->find($id)
   Preacher::findAll();         // Clean, human-readable
   ```

3. **Autoloader**: ✅ Immediately at plugin load (top of sermon.php)
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

4. **Migration Order**: ✅ Core first
   - `sermon.php` → `admin.php` → (frontend deferred to Phase 6)

5. **Frontend Migration**: Deferred to Phase 6 (templating/blocks discussion)

### Dependencies
- None (this is the foundation phase)

### Implementation Steps

1. **Create Service Container** (`src/Services/Container.php`)
   - Singleton pattern
   - Lazy instantiation of repositories
   - Global accessor function `sb_container()`

2. **Create Static Facades** (`src/Facades/`)
   ```
   src/Facades/
   ├── Facade.php           # Base class with __callStatic magic
   ├── Sermon.php           # Sermon::find(), Sermon::findByPreacher()
   ├── Preacher.php         # Preacher::find(), Preacher::findAll()
   ├── Series.php
   ├── Service.php
   ├── File.php
   └── Tag.php
   ```

3. **Load autoloader immediately** (top of `sermon.php`)
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

4. **Migrate core first**:
   - `sermon.php` - Replace $wpdb calls with facades
   - `sb-includes/admin.php` - Replace $wpdb calls with facades
   - `sb-includes/frontend.php` - Defer to Phase 6 (templating)

5. **Add integration tests** for each migrated file

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Autoloader conflicts | Low | High | Test with common plugin combinations |
| Query behavior differences | Medium | Medium | Compare SQL output in tests |
| Performance regression | Low | Medium | Benchmark before/after |

### Estimated Scope
**Medium** - 2-3 weeks of focused work

---

## Phase 2: Split Monolithic Files

### Goals
- Break `admin.php` (2,540 lines) into focused class files
- Restructure `sermon.php` (928 lines) as a thin bootstrap
- Establish clear Single Responsibility Principle boundaries
- Enable better testing and maintenance

### Current State

**admin.php contains 24 functions (VERIFIED):**

| Function | Lines | Responsibility |
|----------|-------|----------------|
| `sb_options()` | ~370 | Options page + form handling |
| `sb_uninstall()` | ~60 | Uninstall UI |
| `sb_templates()` | ~70 | Template editor |
| `sb_manage_preachers()` | ~170 | Preacher CRUD UI |
| `sb_manage_everything()` | ~180 | Series/Services UI |
| `sb_files()` | ~340 | File management UI |
| `sb_manage_sermons()` | ~140 | Sermon list UI |
| `sb_new_sermon()` | ~700 | Add/Edit sermon (largest!) |
| `sb_help()` | ~90 | Help page |
| `sb_japan()` | ~30 | Donation page |
| `sb_do_alerts()` | ~20 | Admin notices |
| `sb_build_textarea()` | ~10 | Form helper |
| `sb_dashboard_glance()` | ~20 | Dashboard widget |
| `sb_scan_dir()` | ~30 | Directory sync |
| `sb_checkSermonUploadable()` | ~25 | Upload validation |
| `sb_delete_unused_tags()` | ~15 | Cleanup |
| `sb_import_options_set()` | ~10 | Import check |
| `sb_print_upload_form()` | ~70 | Upload form |
| `sb_add_help_tabs()` | ~50 | Help tabs |
| Other helpers | ~100 | Various |

**sermon.php mixes:**
- Plugin metadata
- Constants definition
- Hook registration
- Shortcode handler
- Widget initialization
- Option management
- Utility functions

### Target State

```
src/
  Admin/
    AdminController.php              # Route to correct page handler
    Pages/
      OptionsPage.php                # sb_options()
      TemplatesPage.php              # sb_templates()
      PreachersPage.php              # sb_manage_preachers()
      SeriesServicesPage.php         # sb_manage_everything()
      FilesPage.php                  # sb_files()
      SermonsPage.php                # sb_manage_sermons()
      SermonEditorPage.php           # sb_new_sermon() - biggest win!
      HelpPage.php                   # sb_help()
      UninstallPage.php              # sb_uninstall()
    Components/
      AdminNotices.php               # sb_do_alerts()
      FormHelpers.php                # sb_build_textarea(), etc.
      DashboardWidget.php            # sb_dashboard_glance()
    Traits/
      UploadsFiles.php               # Upload handling shared logic
  Plugin.php                         # Main bootstrap class
  Shortcodes/
    SermonShortcode.php              # sb_shortcode()
```

**sermon.php becomes:**
```php
<?php
// Plugin header...
require_once __DIR__ . '/vendor/autoload.php';
SermonBrowser\Plugin::boot();
```

### Key Decisions Needed

1. **Namespace structure**: Flat (`Admin\OptionsPage`) or grouped (`Admin\Pages\OptionsPage`)?
   - Recommendation: Grouped for clarity

2. **Static vs instance methods**: Should admin pages be static handlers or instantiated?
   - Recommendation: Instantiated via container for testability

3. **Template rendering**: Keep inline HTML or introduce a simple template loader?
   - Recommendation: Keep inline initially, extract in Phase 6

### Dependencies
- Phase 1 (Container must exist for DI)

### Implementation Steps

1. **Create `src/Plugin.php`** bootstrap class
   - Move constant definitions
   - Move hook registrations
   - Move init logic from `sb_sermon_init()`

2. **Extract SermonEditorPage first** (biggest ROI)
   - 700 lines of form handling
   - Self-contained CRUD operations
   - Create `src/Admin/Pages/SermonEditorPage.php`

3. **Extract remaining admin pages** in order of complexity:
   - FilesPage (340 lines)
   - OptionsPage (370 lines)
   - SeriesServicesPage (180 lines)
   - PreachersPage (170 lines)
   - SermonsPage (140 lines)
   - Others (smaller)

4. **Create AdminController** to route menu callbacks

5. **Update `sb_add_pages()`** to use new classes

6. **Keep backward-compatible wrappers** in admin.php temporarily

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Missing function calls | Medium | High | Static analysis + comprehensive testing |
| Global variable dependencies | High | Medium | Document and pass explicitly |
| Menu registration changes | Low | Medium | Test all menu paths |

### Estimated Scope
**Large** - 4-6 weeks of focused work

---

## Phase 3: Modernize JavaScript

### Goals
- Introduce a build pipeline (@wordpress/scripts)
- Extract inline JavaScript to ES6+ modules
- Enable proper dependency management
- Prepare infrastructure for Phase 5 Gutenberg blocks

### Current State (VERIFIED 2026-01-30)

**CORRECTION: No separate JS files exist.** All JavaScript is inline in PHP files:

| File | Script Blocks | Lines | Content |
|------|---------------|-------|---------|
| `admin.php` | 16 | ~300 | Form handling, AJAX, dynamic UI |
| `frontend.php` | 5 | ~50 | Cookie handling, datepicker init |
| `ajax.php` | 2 | ~10 | Minor inline scripts |

**Major inline blocks in admin.php:**
- Lines 1665-1770: Sermon editor (createNewPreacher, createNewService, createNewSeries, addPassage, addFile, etc.)
- Lines 1008-1100: File manager
- Lines 455-461, 529-534: Form confirmation dialogs

**Dependencies (WordPress bundled):**
- jQuery
- jQuery UI Datepicker

**No build infrastructure:**
- No `package.json` for frontend
- No bundler configuration
- Scripts loaded via `wp_enqueue_script('jquery')` and `wp_enqueue_script('jquery-ui-datepicker')`

### Target State

```
assets/
  src/
    admin/
      index.js                     # Admin entry point
      sermon-editor.js             # From admin.php:1665-1770
      file-manager.js              # From admin.php:1008-1100
      preacher-form.js             # Preacher CRUD
      utils/
        ajax.js                    # Centralized WP AJAX wrapper
        confirm.js                 # Form confirmation dialogs
    frontend/
      index.js                     # Frontend entry point
      cookie-manager.js            # From frontend.php:330-350
      filter-form.js               # Search/filter handling
  build/                           # Compiled output (gitignored)
    admin.js
    admin.asset.php                # Auto-generated dependencies
    frontend.js
    frontend.asset.php
package.json
```

### Key Decisions (CONFIRMED)

1. **Build tool**: ✅ **@wordpress/scripts** (not Vite)
   - Phase 5 adds Gutenberg blocks - wp-scripts handles this natively
   - Auto-generates `.asset.php` dependency files
   - Handles WordPress script dependencies (`wp.i18n`, `wp.element`)
   - Official WordPress tooling = long-term support

2. **jQuery strategy**: ✅ **Keep jQuery** initially
   - WordPress admin bundles jQuery
   - Gradual migration to vanilla JS in future phases
   - Focus on extraction and modularization first

3. **Date picker**: ✅ **Keep jQuery UI Datepicker**
   - Already bundled with WordPress
   - Works well, no need to replace
   - Flatpickr migration is optional enhancement

4. **TypeScript**: ✅ **Plain JS with JSDoc**
   - Lower barrier, faster iteration
   - JSDoc provides IDE support without compilation

### Dependencies
- **Phase 2 should complete first** - admin.php is being refactored; extracting JS now would create merge conflicts
- Alternative: Start with frontend.php JS (smaller, less churn from Phase 2)

### Implementation Steps

1. **Set up build infrastructure**
   ```bash
   npm init -y
   npm install --save-dev @wordpress/scripts
   ```

2. **Add npm scripts to package.json**
   ```json
   {
     "scripts": {
       "build": "wp-scripts build assets/src/admin/index.js assets/src/frontend/index.js --output-path=assets/build",
       "start": "wp-scripts start assets/src/admin/index.js assets/src/frontend/index.js --output-path=assets/build"
     }
   }
   ```

3. **Extract frontend JS first** (smaller, independent of Phase 2)
   - `frontend.php:330-350` → `frontend/cookie-manager.js`
   - Use `wp_localize_script()` for PHP variables

4. **Extract admin JS** (after Phase 2 stabilizes)
   - `admin.php:1665-1770` → `admin/sermon-editor.js` (~100 lines)
   - `admin.php:1008-1100` → `admin/file-manager.js`
   - Replace `<?php echo $var ?>` with `wp_localize_script()` data

5. **Update PHP enqueue**
   ```php
   $asset = require plugin_dir_path(__FILE__) . 'assets/build/admin.asset.php';
   wp_enqueue_script(
       'sermon-browser-admin',
       plugins_url('assets/build/admin.js', __FILE__),
       $asset['dependencies'],
       $asset['version']
   );
   wp_localize_script('sermon-browser-admin', 'sbAdmin', [
       'ajaxUrl' => admin_url('admin-ajax.php'),
       'nonce' => wp_create_nonce('sb_admin'),
       'i18n' => [
           'newPreacher' => __("New preacher's name?", 'sermon-browser'),
           // ... other strings
       ]
   ]);
   ```

6. **Add to .gitignore**
   ```
   /assets/build/
   /node_modules/
   ```

7. **Update CI/CD** to run `npm run build` before deployment

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| PHP variables in JS | High | Medium | Use `wp_localize_script()` for all data |
| i18n strings in JS | High | Medium | Use `wp_set_script_translations()` |
| Merge conflicts with Phase 2 | Medium | High | Extract frontend JS first; wait for Phase 2 |
| Build failures in CI | Low | High | Add npm build to GitHub Actions |

### Estimated Scope
**Medium** - 2-3 weeks of focused work

---

## Phase 4: Add REST API

### Goals
- Create RESTful endpoints for all sermon operations
- Enable headless/decoupled usage
- Support modern JavaScript consumption
- Maintain backward compatibility with existing shortcodes

### Current State

**No REST API exists (VERIFIED)**
- All data access via shortcodes and admin pages
- AJAX handlers in `sb-includes/ajax.php` use `admin-ajax.php`
- No `register_rest_route()` calls found

**Existing AJAX endpoints handle:**
- Sermon search/filter
- Series/Service CRUD (inline)
- File operations
- Preacher image upload

### Target State

```
src/
  REST/
    RestController.php               # Base controller with auth helpers
    Endpoints/
      SermonsController.php          # /wp-json/sermon-browser/v1/sermons
      PreachersController.php        # /wp-json/sermon-browser/v1/preachers
      SeriesController.php           # /wp-json/sermon-browser/v1/series
      ServicesController.php         # /wp-json/sermon-browser/v1/services
      FilesController.php            # /wp-json/sermon-browser/v1/files
      TagsController.php             # /wp-json/sermon-browser/v1/tags
      SearchController.php           # /wp-json/sermon-browser/v1/search
```

**Endpoints:**
```
GET    /sermons              # List with pagination, filters
GET    /sermons/{id}         # Single sermon with relations
POST   /sermons              # Create (auth required)
PUT    /sermons/{id}         # Update (auth required)
DELETE /sermons/{id}         # Delete (auth required)

GET    /sermons/{id}/files   # Files for sermon
POST   /sermons/{id}/files   # Attach file (auth required)

GET    /preachers            # List all
GET    /preachers/{id}       # Single with sermon count
POST   /preachers            # Create (auth required)

GET    /series               # List all
GET    /series/{id}/sermons  # Sermons in series

GET    /search?q=...&preacher=...&series=...  # Search/filter

GET    /tags                 # Tag cloud data
GET    /tags/{name}/sermons  # Sermons with tag
```

### Key Decisions Needed

1. **Authentication**: WordPress cookie auth, Application Passwords, or custom tokens?
   - Recommendation: **WordPress native** (cookie + nonce for logged-in, Application Passwords for external)

2. **Permission model**: Map to existing WordPress capabilities or custom?
   - Recommendation: Use existing (`publish_posts`, `manage_categories`, etc.)

3. **Response format**: Include related data (embedded) or links (HAL-style)?
   - Recommendation: **Embedded** by default with `?_embed` parameter

4. **Pagination**: Offset-based or cursor-based?
   - Recommendation: Offset-based (matches WordPress standard, simpler)

5. **Rate limiting**: Implement or rely on hosting?
   - Recommendation: Skip initially; add if needed

### Dependencies
- Phase 1 (Repositories provide data access)

### Implementation Steps

1. **Create base RestController**
   ```php
   abstract class RestController extends WP_REST_Controller {
       protected $namespace = 'sermon-browser/v1';
       // Common auth, pagination, response formatting
   }
   ```

2. **Implement SermonsController first**
   - Full CRUD operations
   - Use SermonRepository
   - Add comprehensive tests

3. **Implement read-only endpoints**
   - Preachers, Series, Services, Tags
   - Lower risk, useful for frontend

4. **Implement SearchController**
   - Complex query building
   - Match existing filter capabilities

5. **Update AJAX handlers** to call REST endpoints internally
   - Maintains backward compatibility
   - Single source of truth

6. **Document API** with inline PHPDoc (auto-generates REST schema)

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Security vulnerabilities | Medium | Critical | Permission checks, input validation, capability tests |
| Breaking AJAX consumers | Low | Medium | Keep admin-ajax.php handlers as wrappers |
| Performance issues | Medium | Medium | Caching headers, pagination limits |

### Estimated Scope
**Large** - 4-5 weeks of focused work

---

## Phase 5: Gutenberg Blocks

### Goals
- Create block equivalents for all shortcodes
- Support Block Editor (Gutenberg) natively
- Maintain Classic Editor compatibility
- Enable visual sermon display customization

### Current State

**Shortcodes exist (VERIFIED in sermon.php):**
```php
add_shortcode('sermons', 'sb_shortcode');
add_shortcode('sermon', 'sb_shortcode');
```

**Shortcode attributes:**
- `id` - Single sermon or "latest"
- `preacher` - Filter by preacher ID
- `series` - Filter by series ID
- `service` - Filter by service ID
- `book` - Filter by Bible book
- `date` / `enddate` - Date range
- `tag` - Filter by tag
- `title` - Search by title
- `limit` - Max results
- `filter` / `filterhide` - Filter UI options

**Template tags used (from dictionary.php):**
- `[sermon_title]`, `[preacher_link]`, `[series_link]`, etc.
- Output via `eval()` (addressed in Phase 6)

### Target State

```
src/
  Blocks/
    sermon-list/
      block.json                     # Block metadata
      index.js                       # Editor script
      edit.js                        # Editor component
      save.js                        # Save (dynamic, returns null)
      render.php                     # Server-side render
      style.css                      # Shared styles
      editor.css                     # Editor-only styles
    sermon-single/
      block.json
      index.js
      edit.js
      save.js
      render.php
    sermon-filter/
      block.json
      [...]
    preacher-list/
      block.json
      [...]
    series-grid/
      block.json
      [...]
    tag-cloud/
      block.json
      [...]
```

**Blocks to create:**

| Block | Replaces | Complexity |
|-------|----------|------------|
| Sermon List | `[sermons]` shortcode | High |
| Single Sermon | `[sermon id="X"]` | Medium |
| Sermon Filter | Filter form | Medium |
| Preacher List | Template tag | Low |
| Series Grid | Template tag | Low |
| Tag Cloud | Widget | Low |
| Sermon Player | Embed code | Medium |

### Key Decisions Needed

1. **Block rendering**: Client-side (React) or server-side (PHP)?
   - Recommendation: **Server-side (dynamic blocks)** - simpler, consistent with shortcode output

2. **Styling approach**: Inherit theme styles or provide comprehensive defaults?
   - Recommendation: Minimal defaults with CSS custom properties for theme override

3. **Block patterns**: Create pre-built layouts?
   - Recommendation: Yes, provide 2-3 common patterns (grid, list, featured)

4. **Inner blocks**: Allow nesting/composition?
   - Recommendation: Later phase; start with self-contained blocks

5. **Shortcode deprecation**: Remove shortcodes or keep indefinitely?
   - Recommendation: **Keep indefinitely** for backward compatibility

### Dependencies
- Phase 3 (Build pipeline for JSX/React)
- Phase 4 (REST API for block editor preview)

### Implementation Steps

1. **Set up block development**
   ```bash
   npm install --save-dev @wordpress/scripts @wordpress/blocks @wordpress/components
   ```

2. **Create Sermon List block first** (highest value)
   - Server-rendered content
   - Inspector controls for filtering
   - Live preview via REST API

3. **Create Single Sermon block**
   - Sermon selector in editor
   - Display single sermon content

4. **Create supporting blocks**
   - Tag Cloud (simple, good learning project)
   - Sermon Filter (interactive)
   - Series Grid (visual)

5. **Register block category** for grouping

6. **Create block patterns**
   - "Sermon Archive Page"
   - "Recent Sermons Sidebar"
   - "Featured Sermon"

7. **Update documentation** for block usage

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| React/JSX complexity | Medium | Medium | Start with simple blocks, use components |
| Classic Editor breakage | Low | High | Test both editors |
| WordPress version compatibility | Medium | Medium | Check block API version requirements |

### Estimated Scope
**Large** - 5-7 weeks of focused work

---

## Phase 6: Remove eval() Templates

### Goals
- Eliminate security risk from `eval()` execution
- Improve performance (no runtime code generation)
- Enable proper template caching
- Maintain backward compatibility with existing templates

### Current State

**eval() usage (VERIFIED in sermon.php lines 464, 498):**
```php
// Line 464 - Single sermon
eval('?>' . sb_get_option('single_output'));

// Line 498 - Search results
eval($output);
```

**Template storage:**
- Templates stored in `wp_options` as base64-encoded strings
- `sb_get_option('single_template')` - Raw template with tags
- `sb_get_option('single_output')` - Processed PHP code
- Transformation via `sb_search_results_dictionary()` and `sb_sermon_page_dictionary()`

**Dictionary maps tags to PHP (from dictionary.php):**
```php
'[sermon_title]' => '<?php echo stripslashes($sermon["Sermon"]->title) ?>'
'[preacher_link]' => '<a href="<?php sb_print_preacher_link($sermon["Sermon"]) ?>">...'
```

This means `[sermon_title]` in the template becomes literal PHP code that gets `eval()`'d.

### Target State

**Template Engine Options:**

| Option | Pros | Cons |
|--------|------|------|
| Twig | Popular, safe, feature-rich | External dependency |
| Blade (standalone) | Clean syntax | Laravel association |
| Mustache | Logic-less, simple | Limited features |
| Custom tag parser | No dependencies | Must build from scratch |
| PHP templates | Native, fast | Security concerns |

**Recommendation: Hybrid approach**

1. **New templates**: PHP-based files in `templates/` directory (like WooCommerce)
2. **Legacy support**: Custom parser that converts `[tag]` to function calls (no eval)
3. **Migration path**: Provide template converter tool

**New structure:**
```
templates/
  sermon-single.php              # Modern PHP template
  sermon-list.php
  sermon-filter.php
  partials/
    sermon-card.php
    preacher-info.php
    file-list.php
  legacy/
    compat-renderer.php          # Renders old-style templates safely
src/
  Templates/
    TemplateEngine.php           # Loader and renderer
    TagParser.php                # Converts [tags] to function calls
    TemplateCache.php            # Compiled template cache
    LegacyConverter.php          # Migration tool
```

### Key Decisions Needed

1. **Template format**: Keep `[tag]` syntax or adopt new format?
   - Recommendation: **Keep [tag] syntax** for user familiarity, but render safely

2. **Storage location**: Keep in database or move to files?
   - Recommendation: **Support both** - files for developers, database for users

3. **Caching strategy**: File-based or transient-based?
   - Recommendation: **File-based** in `wp-content/cache/sermon-browser/`

4. **Migration timeline**: Force migration or run indefinitely?
   - Recommendation: Run both systems; deprecate old after 1 year

5. **Default templates**: Ship defaults as files or keep generating?
   - Recommendation: Ship as files, allow database override

### Dependencies
- Phase 1 (Repository layer for data)
- Phase 2 (Clean separation of concerns)

### Implementation Steps

1. **Create TagParser class**
   ```php
   class TagParser {
       public function parse(string $template, array $data): string {
           // Match [tag_name] and call corresponding method
           // NO eval() - direct function calls
       }
   }
   ```

2. **Map all existing tags to methods**
   - Create `TagRenderer` class with a method per tag
   - `renderSermonTitle($sermon)`, `renderPreacherLink($sermon)`, etc.

3. **Create TemplateEngine**
   - Load templates from files or database
   - Use TagParser for old-format templates
   - Direct PHP include for new-format templates

4. **Create default template files**
   - Convert current defaults to PHP templates
   - Use `get_template_part()` style loading

5. **Add template override system**
   - Check theme directory first (like WooCommerce)
   - `theme/sermon-browser/sermon-single.php`

6. **Create LegacyConverter tool**
   - Admin page to convert old templates to new format
   - One-click migration

7. **Update sb_shortcode()** to use TemplateEngine

8. **Remove eval() calls**

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Breaking existing templates | High | High | Keep legacy renderer indefinitely |
| Performance regression | Low | Medium | Template caching |
| User confusion | Medium | Medium | Clear documentation, migration wizard |
| Theme conflicts | Medium | Medium | Namespaced template directory |

### Estimated Scope
**Large** - 4-6 weeks of focused work

---

## Cross-Phase Considerations

### Testing Strategy

Each phase should include:
1. **Unit tests** for new classes (target 80% coverage)
2. **Integration tests** for WordPress interactions
3. **End-to-end tests** for critical user flows
4. **Manual testing** checklist for UI changes

**Current test infrastructure (VERIFIED):**
- PHPUnit 10.5 configured
- Brain/Monkey for WP mocking
- Tests in `tests/Unit/` directory
- Repository tests exist as examples

### Documentation Requirements

1. **Developer docs**: Architecture decisions, API reference
2. **User docs**: Updated feature documentation
3. **Migration guides**: For each breaking change
4. **CHANGELOG**: Detailed version history

### Backward Compatibility Commitment

- PHP 8.0+ required (drop PHP 7.x)
- WordPress 6.0+ required
- Classic Editor supported indefinitely
- Shortcodes supported indefinitely
- Existing templates supported via legacy renderer

### Recommended Phase Order

```
Phase 1 ─────────────────────────────────────────────►
         Phase 2 ─────────────────────────────────────►
              Phase 3 ──────────────────────►
                        Phase 4 ─────────────────────►
                                  Phase 5 ───────────►
         Phase 6 ────────────────────────────────────►
Week:    1    2    3    4    5    6    7    8    9   10
```

Phases 1, 2, and 6 form the "core modernization" track.
Phases 3, 4, and 5 can run partially in parallel.

### Total Estimated Effort

| Phase | Scope | Weeks |
|-------|-------|-------|
| Phase 1: Repository Integration | Medium | 2-3 |
| Phase 2: Split Monoliths | Large | 4-6 |
| Phase 3: JS Modernization | Medium | 2-3 |
| Phase 4: REST API | Large | 4-5 |
| Phase 5: Gutenberg Blocks | Large | 5-7 |
| Phase 6: Remove eval() | Large | 4-6 |
| **Total** | | **21-30 weeks** |

With parallelization, calendar time could be reduced to 12-16 weeks.

---

## Future Features (Post-Refactor)

### WP-CLI Commands for Sermon Management

Create custom WP-CLI commands for managing sermons from the command line:

```bash
# Sermon operations
wp sermon list [--preacher=<id>] [--series=<id>] [--format=<table|json|csv>]
wp sermon get <id>
wp sermon create --title=<title> --preacher=<id> [--series=<id>] [--date=<date>]
wp sermon update <id> [--title=<title>] [--preacher=<id>]
wp sermon delete <id> [--force]

# Preacher operations
wp sermon preacher list
wp sermon preacher create --name=<name>
wp sermon preacher delete <id>

# Series operations
wp sermon series list
wp sermon series create --name=<name>

# Import/Export
wp sermon import <file> [--format=<json|csv>]
wp sermon export [--format=<json|csv>] [--output=<file>]

# Maintenance
wp sermon orphaned-files [--delete]
wp sermon rebuild-stats
```

**Implementation:** Create `src/CLI/SermonCommand.php` extending `WP_CLI_Command`. Register on `cli_init` hook.

**Depends on:** Phase 1 (Repository layer for data access)

---

## Next Steps

1. **Review this plan** with stakeholders
2. **Prioritize phases** based on business needs
3. **Create detailed tickets** for Phase 1
4. **Set up CI/CD** for automated testing
5. **Begin Phase 1** implementation

---

*Generated by Claude Code for Sermon Browser modernization project.*
