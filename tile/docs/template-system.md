# Template System

Sermon Browser includes a powerful template engine for customizing the display of sermons. The template system uses tag-based templating with transient caching and supports both single sermon and multi-sermon list views.

## Overview

The template system consists of two main components:

```php { .api }
/**
 * Template engine - Renders templates with data
 *
 * Class: \SermonBrowser\Templates\TemplateEngine
 */
class TemplateEngine {
    /**
     * Render template with data
     *
     * @param string $templateType 'search' (multi-sermon) or 'single' (single sermon)
     * @param array $data Template variables
     * @return string Rendered HTML
     */
    public function render(string $templateType, array $data): string;
}

/**
 * Template manager - Manages template CRUD operations
 *
 * Class: \SermonBrowser\Templates\TemplateManager
 */
class TemplateManager {
    /**
     * Get template HTML from options
     *
     * @param string $type 'search' or 'single'
     * @return string Template HTML
     */
    public function getTemplate(string $type): string;

    /**
     * Save template HTML to options
     *
     * @param string $type 'search' or 'single'
     * @param string $content Template HTML with tags
     * @return bool True on success
     */
    public function saveTemplate(string $type, string $content): bool;

    /**
     * Get default template
     *
     * @param string $type 'search' or 'single'
     * @return string Default template HTML
     */
    public function getDefaultTemplate(string $type): string;

    /**
     * Migrate templates from legacy format
     *
     * @return array Migration results
     */
    public function migrateFromLegacy(): array;
}
```

## Capabilities

### Template Types

The system supports two template types:

#### Search Template (Multi-Sermon List)

Used for displaying multiple sermons with filtering and pagination.

```php { .api }
/**
 * Search template - Multi-sermon listing
 *
 * Used by:
 * - [sermons] shortcode
 * - Sermon archive pages
 * - Filtered sermon lists
 * - Sermon widgets
 *
 * Available data in $data array:
 * - 'sermons' (array): Array of sermon objects
 * - 'pagination' (string): Pagination HTML
 * - 'filter_html' (string): Filter UI HTML
 * - 'total' (int): Total sermon count
 * - 'page' (int): Current page number
 * - 'limit' (int): Sermons per page
 */
```

#### Single Template (Single Sermon)

Used for displaying a single sermon's complete details.

```php { .api }
/**
 * Single template - Single sermon display
 *
 * Used by:
 * - [sermon] shortcode
 * - Single sermon pages
 * - Sermon detail views
 *
 * Available data in $data array:
 * - 'sermon' (object): Sermon object with all fields
 * - 'files' (array): Array of file objects
 * - 'tags' (array): Array of tag objects
 * - 'books' (array): Array of Bible book names
 * - 'preacher' (object): Preacher object
 * - 'series' (object): Series object
 * - 'service' (object): Service object
 */
```

### Template Tags

Templates use placeholder tags that are replaced with dynamic content:

```php { .api }
/**
 * Core Template Tags
 */

// Basic sermon information
[title]              // Sermon title
[preacher]           // Preacher name with link
[series]             // Series name with link
[service]            // Service name with link
[date]               // Formatted sermon date
[description]        // Sermon description/notes
[book]               // Bible book name with link
[tags]               // Tag list with links

// Bible text tags (requires bible_passage field)
[esvtext]            // ESV Bible text
[nettext]            // NET Bible text
[kjvtext]            // KJV Bible text
[nivtext]            // NIV Bible text
[msgtext]            // MSG Bible text

// File and media tags
[files]              // All attached files as links
[url]                // First URL link
[mp3]                // First MP3 file player
[video]              // Video embed code
[code]               // Alternate embed code

// Navigation and metadata
[edit]               // Edit link (if user has permission)
[permalink]          // Link to single sermon page
[download_count]     // Total download count

// List-only tags (search template)
[pagination]         // Pagination controls
[filters]            // Filter UI controls
[sermon_count]       // Total sermon count

// Conditional tags
[if_files]...[/if_files]           // Show if files exist
[if_description]...[/if_description] // Show if description exists
[if_tags]...[/if_tags]             // Show if tags exist
```

### Using Template Engine

```php { .api }
use SermonBrowser\Templates\TemplateEngine;

$engine = new TemplateEngine();

// Render single sermon
$sermon = Sermon::findWithRelations(123);
$html = $engine->render('single', [
    'sermon' => $sermon,
    'files' => $sermon->files,
    'tags' => $sermon->tags
]);
echo $html;

// Render sermon list
$sermons = Sermon::findAll(['series' => 12], 10);
$html = $engine->render('search', [
    'sermons' => $sermons,
    'pagination' => sb_print_pagination_html(),
    'filter_html' => sb_get_filter_html('dropdown'),
    'total' => count($sermons),
    'page' => 1,
    'limit' => 10
]);
echo $html;
```

### Customizing Templates

Templates can be customized via WordPress admin:

**Location:** Sermons > Options > Templates

**Admin Interface:**
- View current templates
- Edit template HTML
- Reset to defaults
- Preview changes

**Programmatic Access:**

```php { .api }
use SermonBrowser\Templates\TemplateManager;

$manager = new TemplateManager();

// Get current template
$template = $manager->getTemplate('search');

// Modify template
$template = str_replace('[title]', '<h2>[title]</h2>', $template);

// Save modified template
$manager->saveTemplate('search', $template);

// Reset to default
$default = $manager->getDefaultTemplate('search');
$manager->saveTemplate('search', $default);
```

### Default Search Template

```html
<div class="sermon-list">
    [filters]

    <div class="sermon-entries">
        <!-- Loop: This section repeats for each sermon -->
        <article class="sermon-entry">
            <h3 class="sermon-title">[title]</h3>
            <div class="sermon-meta">
                <span class="preacher">Preacher: [preacher]</span>
                <span class="date">Date: [date]</span>
                <span class="series">Series: [series]</span>
                <span class="book">Book: [book]</span>
            </div>
            <div class="sermon-description">
                [description]
            </div>
            <div class="sermon-tags">
                Tags: [tags]
            </div>
            <div class="sermon-files">
                [files]
            </div>
        </article>
        <!-- End loop -->
    </div>

    [pagination]
</div>
```

### Default Single Template

```html
<article class="sermon-single">
    <header class="sermon-header">
        <h1 class="sermon-title">[title]</h1>
        <div class="sermon-meta">
            <span class="preacher">
                <strong>Preacher:</strong> [preacher]
            </span>
            <span class="series">
                <strong>Series:</strong> [series]
            </span>
            <span class="service">
                <strong>Service:</strong> [service]
            </span>
            <span class="date">
                <strong>Date:</strong> [date]
            </span>
        </div>
    </header>

    <div class="sermon-passage">
        <strong>Bible Passage:</strong> [book]
        [esvtext]
    </div>

    <div class="sermon-description">
        [description]
    </div>

    <div class="sermon-media">
        <h3>Media Files</h3>
        [files]
        [video]
    </div>

    <div class="sermon-tags">
        <strong>Topics:</strong> [tags]
    </div>

    <footer class="sermon-footer">
        [edit]
    </footer>
</article>
```

### Template Caching

The template system uses WordPress transients for caching:

```php { .api }
/**
 * Cache configuration
 *
 * - Cache key format: 'sb_template_{type}_{hash}'
 * - Cache TTL: 60 minutes (3600 seconds)
 * - Cache cleared on: sermon create/update/delete
 */

// Manual cache clearing
delete_transient('sb_template_search_' . md5($template));
delete_transient('sb_template_single_' . md5($template));

// Clear all sermon browser caches
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_sb_template_%'"
);
```

### Advanced Template Customization

#### Loop Control

The search template automatically loops through sermons. To customize the loop:

```html
<!-- Custom sermon loop structure -->
<div class="sermons-grid">
    <!-- Sermon item (repeats) -->
    <div class="sermon-card">
        <div class="card-header">
            <h4>[title]</h4>
            <span class="date">[date]</span>
        </div>
        <div class="card-body">
            <p>[description]</p>
        </div>
        <div class="card-footer">
            <span class="preacher">[preacher]</span>
            <span class="series">[series]</span>
        </div>
    </div>
</div>
```

#### Conditional Content

Use conditional tags to show content only when data exists:

```html
[if_description]
    <div class="sermon-description">
        [description]
    </div>
[/if_description]

[if_files]
    <div class="sermon-files">
        <h4>Available Files</h4>
        [files]
    </div>
[/if_files]

[if_tags]
    <div class="sermon-tags">
        Topics: [tags]
    </div>
[/if_tags]
```

#### Custom CSS Classes

Add custom CSS classes for styling:

```html
<article class="sermon custom-sermon-style">
    <h2 class="custom-title">[title]</h2>
    <div class="custom-meta">
        <span class="meta-item meta-preacher">[preacher]</span>
        <span class="meta-item meta-date">[date]</span>
    </div>
</article>
```

### Template Migration

The system includes automatic migration from legacy template formats:

```php { .api }
use SermonBrowser\Templates\TemplateManager;

$manager = new TemplateManager();

// Migrate legacy templates on plugin activation
$results = $manager->migrateFromLegacy();

// Results array contains:
// - 'search_migrated' (bool): Whether search template was migrated
// - 'single_migrated' (bool): Whether single template was migrated
// - 'errors' (array): Any migration errors
```

### Template Debugging

Enable WordPress debug mode to see template rendering information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Template errors will be logged to wp-content/debug.log
```

### Best Practices

1. **Always test changes**: Preview templates before saving
2. **Use conditional tags**: Show content only when it exists
3. **Keep templates simple**: Complex logic belongs in code, not templates
4. **Cache clearing**: Clear cache after template changes
5. **Backup templates**: Save custom templates before upgrading plugin
6. **Valid HTML**: Ensure templates produce valid HTML structure
7. **Accessibility**: Include proper ARIA labels and semantic HTML

### Template Storage

Templates are stored in WordPress options:

```php { .api }
// Option keys
// - 'sb_template_search': Search template HTML
// - 'sb_template_single': Single template HTML

// Direct access (use TemplateManager instead)
$search_template = get_option('sb_template_search');
$single_template = get_option('sb_template_single');
```
