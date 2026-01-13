# Feature Plan: Aggressive Architectural Refactoring

Created: 2026-01-13
Author: architect-agent
Builds on: sermon-browser-modernization.md (v0.5.0 released)

## Overview

Transform Sermon Browser from 99% procedural code (122 global sb_* functions, 2,540-line admin.php) into a modern OOP architecture with Repository pattern, PSR-4 autoloading, and modular components. This is an aggressive refactoring that can break backward compatibility when properly documented.

## Requirements

- [ ] Split admin.php (2,540 lines) into manageable modules (<500 lines each)
- [ ] Add Repository pattern for database abstraction (eliminate 192 scattered queries)
- [ ] Implement PSR-4 autoloading with SermonBrowser\ namespace
- [ ] Convert procedural functions to class methods
- [ ] Maintain plugin functionality throughout (no big-bang rewrite)
- [ ] Document all breaking changes clearly

## Design

### Target Architecture

```
sermon-browser/
├── sermon.php                    # Bootstrap only (~100 lines)
├── src/                          # PSR-4 root: SermonBrowser\
│   ├── Plugin.php               # Main plugin class (singleton)
│   ├── Contracts/               # Interfaces
│   │   ├── RepositoryInterface.php
│   │   └── RendererInterface.php
│   ├── Repositories/            # Database abstraction
│   │   ├── AbstractRepository.php
│   │   ├── SermonRepository.php
│   │   ├── PreacherRepository.php
│   │   ├── SeriesRepository.php
│   │   ├── ServiceRepository.php
│   │   ├── FileRepository.php
│   │   └── TagRepository.php
│   ├── Admin/                   # Admin functionality
│   │   ├── AdminController.php  # Main admin orchestrator
│   │   ├── Pages/              
│   │   │   ├── SermonsPage.php
│   │   │   ├── NewSermonPage.php
│   │   │   ├── PreachersPage.php
│   │   │   ├── ManagePage.php
│   │   │   ├── FilesPage.php
│   │   │   ├── OptionsPage.php
│   │   │   └── TemplatesPage.php
│   │   └── Ajax/
│   │       ├── AjaxHandler.php
│   │       ├── PreacherAjax.php
│   │       ├── SeriesAjax.php
│   │       ├── ServiceAjax.php
│   │       └── FileAjax.php
│   ├── Frontend/               # Public display
│   │   ├── FrontendController.php
│   │   ├── SermonDisplay.php
│   │   ├── FilterDisplay.php
│   │   └── BibleIntegration.php
│   ├── Widgets/                # WordPress widgets
│   │   ├── SermonsWidget.php
│   │   ├── TagCloudWidget.php
│   │   └── PopularWidget.php
│   ├── Feed/                   # Podcast/RSS
│   │   └── PodcastGenerator.php
│   ├── Templates/              # Template engine
│   │   ├── TemplateEngine.php
│   │   └── Dictionary.php
│   └── Installer/
│       ├── Installer.php
│       └── Upgrader.php
├── sb-includes/                 # DEPRECATED (backward compat wrappers)
│   └── legacy-functions.php     # Function aliases to class methods
└── templates/                   # Default templates
    ├── single-sermon.php
    └── sermon-list.php
```

### Repository Pattern

```php
namespace SermonBrowser\Repositories;

interface RepositoryInterface {
    public function find(int $id): ?object;
    public function findAll(array $criteria = [], int $limit = 0, int $offset = 0): array;
    public function count(array $criteria = []): int;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

class SermonRepository implements RepositoryInterface {
    private \wpdb $db;
    private string $table;
    
    public function __construct(\wpdb $db) {
        $this->db = $db;
        $this->table = $db->prefix . 'sb_sermons';
    }
    
    public function find(int $id): ?object {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d",
                $id
            )
        );
    }
    
    public function findWithRelations(int $id): ?object {
        return $this->db->get_row($this->db->prepare(
            "SELECT m.*, p.name as preacher_name, s.name as series_name, 
                    sv.name as service_name
             FROM {$this->table} m
             LEFT JOIN {$this->db->prefix}sb_preachers p ON m.preacher_id = p.id
             LEFT JOIN {$this->db->prefix}sb_series s ON m.series_id = s.id
             LEFT JOIN {$this->db->prefix}sb_services sv ON m.service_id = sv.id
             WHERE m.id = %d",
            $id
        ));
    }
    
    public function create(array $data): int {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id;
    }
    
    public function delete(int $id): bool {
        // Cascade: tags, books, files
        $this->db->delete("{$this->db->prefix}sb_sermons_tags", ['sermon_id' => $id]);
        $this->db->delete("{$this->db->prefix}sb_books_sermons", ['sermon_id' => $id]);
        $this->db->update("{$this->db->prefix}sb_stuff", 
            ['sermon_id' => 0], 
            ['sermon_id' => $id, 'type' => 'file']
        );
        $this->db->delete("{$this->db->prefix}sb_stuff", 
            ['sermon_id' => $id]  // Non-file attachments
        );
        return $this->db->delete($this->table, ['id' => $id]) !== false;
    }
}
```

### Data Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                         WordPress Request                           │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│ sermon.php (Bootstrap)                                              │
│   - Composer autoload                                               │
│   - Plugin::instance()->init()                                      │
└─────────────────────────────────────────────────────────────────────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            ▼                       ▼                       ▼
    ┌───────────────┐       ┌───────────────┐       ┌───────────────┐
    │ AdminController│       │FrontendController│     │ AjaxHandler   │
    │ (is_admin)    │       │ (frontend)    │       │ (wp_ajax_*)   │
    └───────────────┘       └───────────────┘       └───────────────┘
            │                       │                       │
            └───────────────────────┼───────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        Repository Layer                             │
│   SermonRepository | PreacherRepository | SeriesRepository | ...    │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         WordPress $wpdb                             │
└─────────────────────────────────────────────────────────────────────┘
```

### Breaking Changes Documentation Format

```markdown
## BREAKING CHANGES in v1.0.0

### Functions Removed
| Function | Replacement | Migration |
|----------|-------------|-----------|
| sb_get_sermons() | SermonBrowser\Plugin::sermons()->findAll() | See migration guide |

### Hooks Changed
| Old Hook | New Hook | Notes |
|----------|----------|-------|
| sb_after_sermon_save | sermon_browser/sermon/saved | Now passes Sermon object |

### Template Tags Changed
| Old Tag | New Tag | Notes |
|---------|---------|-------|
| [preacher] | [sb:preacher] | Namespaced to avoid conflicts |
```

## Dependencies

| Dependency | Type | Reason |
|------------|------|--------|
| Composer | Tool | PSR-4 autoloading |
| WordPress 6.0+ | Platform | Modern hooks and APIs |
| PHP 8.0+ | Platform | Type declarations, named arguments |

## Implementation Phases

### Phase 1: Foundation (Repository Layer)
**Priority:** CRITICAL - All other phases depend on this
**Estimated effort:** 8-12 hours
**Risk:** Low (additive, no breaking changes yet)

**Files to create:**
```
src/
├── Contracts/
│   └── RepositoryInterface.php
├── Repositories/
│   ├── AbstractRepository.php
│   ├── SermonRepository.php
│   ├── PreacherRepository.php
│   ├── SeriesRepository.php
│   ├── ServiceRepository.php
│   ├── FileRepository.php
│   └── TagRepository.php
```

**Files to modify:**
- `composer.json` - Add PSR-4 autoload

**Acceptance Criteria:**
- [ ] All repositories implement RepositoryInterface
- [ ] Unit tests for each repository CRUD operation
- [ ] Repositories work alongside existing procedural code
- [ ] No database schema changes required

**Migration Guide:**
1. Repositories are introduced as parallel code path
2. Existing functions continue working
3. New code should use repositories

**Test Strategy:**
```php
class SermonRepositoryTest extends TestCase {
    public function test_find_returns_sermon_with_relations(): void
    public function test_create_returns_insert_id(): void
    public function test_delete_cascades_to_related_tables(): void
    public function test_findAll_respects_limit_and_offset(): void
}
```

### Phase 2: Plugin Core and Admin Split
**Priority:** HIGH - Enables modular development
**Estimated effort:** 10-15 hours  
**Risk:** Medium (touches core initialization)
**Depends on:** Phase 1

**Files to create:**
```
src/
├── Plugin.php
├── Admin/
│   ├── AdminController.php
│   ├── Pages/
│   │   ├── SermonsPage.php
│   │   ├── NewSermonPage.php
│   │   ├── PreachersPage.php
│   │   ├── ManagePage.php
│   │   ├── FilesPage.php
│   │   └── OptionsPage.php
```

**Files to modify:**
- `sermon.php` - Convert to bootstrap only
- `sb-includes/admin.php` - Extract to classes (keep wrapper functions)

**Admin.php Decomposition Map:**

| Lines | Function | Target Class |
|-------|----------|--------------|
| 29-407 | sb_options() | Pages/OptionsPage.php |
| 408-468 | sb_uninstall() | Installer/Installer.php |
| 469-541 | sb_templates() | Pages/TemplatesPage.php |
| 542-713 | sb_manage_preachers() | Pages/PreachersPage.php |
| 714-899 | sb_manage_everything() | Pages/ManagePage.php |
| 900-1247 | sb_files() | Pages/FilesPage.php |
| 1248-1395 | sb_manage_sermons() | Pages/SermonsPage.php |
| 1396-2094 | sb_new_sermon() | Pages/NewSermonPage.php |
| 2095-2207 | sb_help(), sb_japan() | Pages/HelpPage.php |
| 2208-2541 | Utility functions | AdminController.php |

**Acceptance Criteria:**
- [ ] Plugin.php singleton manages initialization
- [ ] Each admin page is a separate class
- [ ] Old functions work as wrappers to new classes
- [ ] Admin menu structure unchanged
- [ ] All admin pages load without errors

**Breaking Changes:**
- None in this phase (backward compat maintained)

### Phase 3: Ajax Modularization
**Priority:** HIGH - Security and maintainability
**Estimated effort:** 4-6 hours
**Risk:** Medium (AJAX is fragile)
**Depends on:** Phase 1, Phase 2

**Files to create:**
```
src/Admin/Ajax/
├── AjaxHandler.php        # Central dispatcher with nonce verification
├── PreacherAjax.php       # Preacher CRUD operations
├── SeriesAjax.php         # Series CRUD operations
├── ServiceAjax.php        # Service CRUD operations
└── FileAjax.php           # File operations
```

**Current ajax.php Analysis:**

```
Lines 5-20:   $_POST['pname'] - Preacher CRUD
Lines 21-44:  $_POST['sname'] - Service CRUD  
Lines 45-60:  $_POST['ssname'] - Series CRUD
Lines 61-100: $_POST['fname'] - File operations
Lines 101-149: $_POST['fetch'] - Sermon pagination
Lines 150-193: $_POST['fetchU/fetchL/search'] - File pagination
```

**Acceptance Criteria:**
- [ ] All AJAX handlers use proper WordPress AJAX API (wp_ajax_*)
- [ ] Nonce verification on all endpoints
- [ ] Permission checks on all CRUD operations
- [ ] Consistent JSON response format
- [ ] No direct output (proper wp_send_json_*)

**Breaking Changes:**
- AJAX endpoints will use new action names: `wp_ajax_sb_*`
- Must update JavaScript to use new endpoint names

### Phase 4: Frontend Modularization
**Priority:** MEDIUM - Large but less risky
**Estimated effort:** 8-10 hours
**Risk:** Low (mostly display code)
**Depends on:** Phase 1

**Files to create:**
```
src/Frontend/
├── FrontendController.php    # Main entry point
├── SermonDisplay.php         # Single/multiple sermon rendering
├── FilterDisplay.php         # Search/filter UI
├── BibleIntegration.php      # Bible API integration
└── NavigationHelper.php      # Pagination, links
```

**Frontend.php Function Mapping:**

| Function | Target Class | Notes |
|----------|--------------|-------|
| sb_display_sermons() | SermonDisplay::render() | Main shortcode |
| sb_widget_sermon() | (widgets) | Already migrated |
| sb_widget_tag_cloud() | (widgets) | Already migrated |
| sb_widget_popular() | (widgets) | Already migrated |
| sb_print_filter_line() | FilterDisplay::renderFilter() | |
| sb_print_date_filter_line() | FilterDisplay::renderDateFilter() | |
| sb_print_filters() | FilterDisplay::renderAll() | |
| sb_tidy_reference() | BibleIntegration::formatReference() | |
| sb_add_*_text() | BibleIntegration::fetchText() | Multiple APIs |
| sb_print_*_link() | NavigationHelper::link*() | |

**Acceptance Criteria:**
- [ ] All frontend display uses repository layer
- [ ] Template tags continue to work
- [ ] Shortcode output unchanged
- [ ] Bible API calls abstracted

**Breaking Changes:**
- Template tag syntax may change in future phase
- Document that direct function calls are deprecated

### Phase 5: Template Engine Modernization
**Priority:** MEDIUM - Improves maintainability
**Estimated effort:** 6-8 hours
**Risk:** HIGH (eval-based system, user customizations)
**Depends on:** Phase 4

**Current Problem:**
The template system uses `eval()` to process PHP in user-defined templates stored in the database. This is a security concern and prevents opcode caching.

**Files to create:**
```
src/Templates/
├── TemplateEngine.php      # Secure template processor
├── Dictionary.php          # Tag definitions (from dictionary.php)
└── tags/                   # Individual tag handlers
    ├── SermonTags.php
    ├── PreacherTags.php
    └── MediaTags.php

templates/                  # Default template files
├── single-sermon.php
├── sermon-list.php
└── excerpt.php
```

**New Template Approach:**

Option A: **Shortcode-based (Recommended)**
```php
// Old: <?php echo $title; ?>
// New: [sb:title]

// Old: <?php foreach ($sermons as $sermon): ?>
// New: [sb:sermons][sb:title][/sb:sermons]
```

Option B: **Mustache/Handlebars-style**
```php
// {{title}}
// {{#sermons}}{{title}}{{/sermons}}
```

**Migration Strategy:**
1. Keep eval() system working for existing users
2. Add new shortcode system as alternative
3. Provide converter tool: eval templates -> shortcode templates
4. Deprecate eval system after 2 releases
5. Remove eval system in v2.0

**Acceptance Criteria:**
- [ ] New template engine supports all existing tags
- [ ] No eval() required for new templates
- [ ] Migration tool converts existing templates
- [ ] Performance improved (opcache works)
- [ ] Documentation for new template syntax

**Breaking Changes:**
| Old Template Tag | New Tag | Notes |
|-----------------|---------|-------|
| <?php echo $title; ?> | [sb:title] | Shortcode style |
| [passages_loop] ... [/passages_loop] | [sb:passages]...[/sb:passages] | Prefixed |
| %TITLE% | [sb:title] | Unified syntax |

### Phase 6: Widget Consolidation
**Priority:** LOW - Already partially done
**Estimated effort:** 2-4 hours
**Risk:** Low
**Depends on:** Phase 4

**Files to consolidate:**
```
# Move from sb-includes/widget.php to:
src/Widgets/
├── SermonsWidget.php       # From SB_Sermons_Widget class
├── TagCloudWidget.php      # From SB_Tag_Cloud_Widget class  
└── PopularWidget.php       # From SB_Popular_Widget class
```

**Acceptance Criteria:**
- [ ] Widgets use repository layer
- [ ] Widget settings preserved
- [ ] Block-based alternatives documented (future)

### Phase 7: Legacy Deprecation Layer
**Priority:** HIGH (parallel with all phases)
**Estimated effort:** Ongoing (1-2 hours per phase)
**Risk:** Low
**Depends on:** All phases

**File to create:**
```
sb-includes/legacy-functions.php
```

**Pattern for all deprecated functions:**
```php
<?php
/**
 * Legacy function wrappers for backward compatibility.
 * 
 * @deprecated 1.0.0 Use SermonBrowser\Plugin class methods instead.
 * @package SermonBrowser\Legacy
 */

if (!function_exists('sb_manage_sermons')) {
    /**
     * @deprecated 1.0.0 Use SermonBrowser\Admin\Pages\SermonsPage::render()
     */
    function sb_manage_sermons() {
        _deprecated_function(__FUNCTION__, '1.0.0', 
            'SermonBrowser\\Admin\\Pages\\SermonsPage::render()');
        return SermonBrowser\Plugin::instance()
            ->admin()
            ->page('sermons')
            ->render();
    }
}

if (!function_exists('sb_get_single_sermon')) {
    /**
     * @deprecated 1.0.0 Use SermonBrowser\Plugin::sermons()->find($id)
     */
    function sb_get_single_sermon($id) {
        _deprecated_function(__FUNCTION__, '1.0.0',
            'SermonBrowser\\Plugin::sermons()->find()');
        return SermonBrowser\Plugin::sermons()->findWithRelations($id);
    }
}
```

**Hook Migration:**
```php
// In Plugin.php
add_action('init', function() {
    // Forward old hooks to new ones
    add_action('sb_before_sermon_display', function($sermon) {
        do_action('sermon_browser/sermon/before_display', $sermon);
    });
    
    // Deprecated hook warning
    if (has_action('sb_after_sermon_save')) {
        _deprecated_hook('sb_after_sermon_save', '1.0.0', 
            'sermon_browser/sermon/saved');
    }
});
```

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking user templates | HIGH | Keep eval() working, provide migration tool |
| AJAX endpoint changes break JS | MEDIUM | Version JS endpoints, gradual deprecation |
| Repository queries differ from direct SQL | MEDIUM | Extensive test coverage, run both in parallel |
| Plugin too large to refactor atomically | HIGH | Phased approach, each phase is standalone |
| Performance regression | MEDIUM | Benchmark before/after, optimize repositories |
| Backward compat layer adds complexity | LOW | Clear deprecation timeline, remove in v2.0 |

## Open Questions

- [ ] Should we support Block Editor widgets? (Defer to future release?)
- [ ] Should repositories cache results? (Probably yes, with transients)
- [ ] How long to maintain legacy function wrappers? (Suggest: 2 major versions)
- [ ] Should template migration be automatic or opt-in? (Suggest: opt-in with tool)

## Success Criteria

1. **Code Quality**
   - [ ] No file > 500 lines
   - [ ] All classes have single responsibility
   - [ ] 80%+ test coverage on new code
   - [ ] PHPStan level 5 passes

2. **Functionality**
   - [ ] All existing features work
   - [ ] No PHP errors/warnings on PHP 8.2
   - [ ] All admin pages functional
   - [ ] Shortcode output unchanged

3. **Developer Experience**
   - [ ] Clear namespace structure
   - [ ] IDE autocompletion works
   - [ ] Documentation for all public APIs
   - [ ] Migration guide for each breaking change

4. **Performance**
   - [ ] Page load time <= v0.5.0
   - [ ] Database queries reduced by 20%+
   - [ ] Opcache effective for all code

## Timeline Estimate

| Phase | Effort | Calendar (1 dev) |
|-------|--------|------------------|
| 1. Repository Layer | 8-12h | Week 1 |
| 2. Plugin Core & Admin | 10-15h | Week 1-2 |
| 3. Ajax Modularization | 4-6h | Week 2 |
| 4. Frontend Modularization | 8-10h | Week 2-3 |
| 5. Template Engine | 6-8h | Week 3 |
| 6. Widget Consolidation | 2-4h | Week 3 |
| 7. Legacy Layer | Ongoing | Throughout |
| **Total** | **40-60h** | **3-4 weeks** |

## Version Strategy

- **v0.6.0**: Phase 1 complete (repositories introduced, no breaking changes)
- **v0.7.0**: Phases 2-3 complete (admin/ajax restructured)  
- **v0.8.0**: Phases 4-5 complete (frontend/templates)
- **v0.9.0**: Phase 6 complete, all deprecation notices active
- **v1.0.0**: Legacy layer optional, new architecture default

---

## Appendix A: Complete Function Inventory

### sb-includes/admin.php (47 functions)
```
sb_add_admin_headers     sb_options              sb_display_error
sb_display_warning       sb_uninstall            sb_templates
sb_manage_preachers      sb_manage_everything    sb_files
sb_manage_sermons        sb_new_sermon           sb_help
sb_japan                 sb_do_alerts            sb_build_textarea
sb_rightnow              sb_dashboard_glance     sb_scan_dir
sb_checkSermonUploadable sb_delete_unused_tags   sb_import_options_set
sb_print_import_options_message                  sb_print_upload_form
sb_add_help_tabs         sb_add_contextual_help
```

### sb-includes/frontend.php (52 functions)
```
sb_display_sermons       sb_widget_sermon        sb_widget_tag_cloud
sb_admin_bar_menu        sb_sort_object          sb_widget_popular
sb_print_most_popular    sb_page_title           sb_download_page
sb_tidy_reference        sb_print_bible_passage  sb_get_books
sb_add_bible_text        sb_add_esv_text         sb_get_xml
sb_add_net_text          sb_add_other_bibles     sb_edit_link
sb_build_url             sb_add_headers          sb_formatted_date
sb_podcast_url           sb_print_sermon_link    sb_print_preacher_link
sb_print_series_link     sb_print_service_link   sb_get_book_link
sb_get_tag_link          sb_print_tags           sb_print_tag_clouds
sb_print_next_page_link  sb_print_prev_page_link sb_print_url
sb_print_url_link        sb_print_code           sb_print_preacher_description
sb_print_preacher_image  sb_print_next_sermon_link
sb_print_prev_sermon_link sb_print_sameday_sermon_link
sb_get_single_sermon     sb_print_filter_line    sb_print_date_filter_line
sb_url_minus_parameter   sb_print_filters        sb_first_mp3
```

### Other files (23 functions)
```
# podcast.php (6)
sb_print_iso_date  sb_media_size  sb_mp3_duration
sb_xml_entity_encode  sb_podcast_file_url  sb_mime_type

# dictionary.php (2)
sb_search_results_dictionary  sb_sermon_page_dictionary

# upgrade.php (3)
sb_upgrade_options  sb_version_upgrade  sb_database_upgrade

# sb-install.php (5)
sb_install  sb_default_multi_template  sb_default_single_template
sb_default_css  sb_default_excerpt_template

# functions-testable.php (4)
sb_generate_temp_suffix  sb_join_passages  sb_get_locale_string
sb_is_super_admin

# widget.php (3)
sb_widget_sermon_init  sb_migrate_widget_settings  (widget classes)
```

## Appendix B: Database Query Locations

**Total: 192 direct $wpdb calls across 8 files**

| File | Count | Primary Tables |
|------|-------|----------------|
| admin.php | 91 | All tables |
| frontend.php | 24 | sb_sermons, sb_stuff |
| sb-install.php | 27 | Schema creation |
| ajax.php | 22 | All tables |
| upgrade.php | 21 | Schema migrations |
| widget.php | 3 | sb_sermons, sb_stuff |
| podcast.php | 2 | sb_stuff |
| uninstall.php | 2 | All tables (DROP) |

These will all be replaced with Repository method calls.
