# Sermon Browser

Sermon Browser is a comprehensive WordPress plugin that enables churches to upload, manage, and display sermons on their websites with full podcasting capabilities. The plugin provides a complete sermon management system including search functionality by topic, preacher, bible passage, and date; support for multiple file types (MP3, PDF, PowerPoint, video embeds from YouTube/Vimeo); customizable sidebar widgets; built-in media player for MP3 files; RSS/iTunes podcast feeds with custom filtering; automatic ID3 tag parsing; and integration with multiple Bible versions.

## Package Information

- **Package Name**: sermon-browser
- **Package Type**: WordPress Plugin (Composer)
- **Language**: PHP 8.0+
- **WordPress Version**: 6.0+
- **Installation**: Install via Composer (`composer require sermon-browser/sermon-browser`) or upload plugin folder to `/wp-content/plugins/` and activate in WordPress admin

## Core Imports

```php
// Facade classes for database operations
use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Facades\Book;

// Template engine
use SermonBrowser\Templates\TemplateEngine;
use SermonBrowser\Templates\TemplateManager;

// Block classes
use SermonBrowser\Blocks\BlockRegistry;

// REST API controllers
use SermonBrowser\REST\SermonsController;
use SermonBrowser\REST\PreachersController;

// Widget classes
use SermonBrowser\Widgets\SermonsWidget;
use SermonBrowser\Widgets\TagCloudWidget;
use SermonBrowser\Widgets\PopularWidget;

// Plugin automatically initializes when WordPress loads
// Main initialization occurs on the 'plugins_loaded' and 'init' hooks
// No manual setup required - plugin is ready to use after activation
```

## Basic Usage

```php
// Display sermons using shortcode in post/page content
echo do_shortcode('[sermons limit="10" filter="dropdown"]');

// Or use in template files
if (function_exists('sb_display_sermons')) {
    sb_display_sermons([
        'limit' => 10,
        'filter' => 'dropdown'
    ]);
}

// Get sermons programmatically
$sermons = sb_get_sermons(
    ['preacher' => 5, 'series' => 2], // filter
    'DESC',                            // order
    1,                                 // page
    10,                                // limit
    false                              // hide_empty
);

foreach ($sermons as $sermon) {
    echo '<h3>' . esc_html($sermon->title) . '</h3>';
    echo '<p>' . esc_html($sermon->preacher_name) . '</p>';
}
```

## Architecture

The plugin uses a modern PSR-4 architecture with the following key components:

- **Facades**: Static API for database operations (`\SermonBrowser\Facades\*`)
- **Repositories**: Database access layer (`\SermonBrowser\Repositories\*`)
- **REST API**: RESTful endpoints for AJAX and external access (`\SermonBrowser\REST\*`)
- **Blocks**: Gutenberg block components (`\SermonBrowser\Blocks\*`)
- **Widgets**: Traditional WordPress widgets (`\SermonBrowser\Widgets\*`)
- **Template System**: Customizable rendering engine (`\SermonBrowser\Templates\*`)
- **Security**: Input sanitization and CSRF protection (`\SermonBrowser\Security\*`)

## Capabilities

### Shortcodes

Display sermons on any page or post using the `[sermons]` and `[sermon]` shortcodes with extensive filtering and display options including dropdown filters, one-click filters, and conditional display.

```php { .api }
// Display multiple sermons with filtering
[sermons filter="dropdown" limit="10" preacher="5" series="2"]

// Display single sermon
[sermon id="123"]
```

[Shortcodes](./shortcodes.md)

### WordPress Widgets

Three widget types for sidebar display: sermon list widget with filtering options, tag cloud widget for sermon topics, and popular sermons/series/preachers widget.

```php { .api }
// Widgets registered via widgets_init hook
class SermonsWidget extends WP_Widget // Display recent sermons
class TagCloudWidget extends WP_Widget // Display tag cloud
class PopularWidget extends WP_Widget // Display popular content
```

[Widgets](./widgets.md)

### Gutenberg Blocks

Twelve Gutenberg blocks for Full Site Editing including sermon lists, single sermon display, tag clouds, series grids, media players, preacher profiles, and filter controls, plus five pre-built block patterns.

```php { .api }
// Block namespace: sermon-browser
sermon-browser/sermon-list      // Filterable sermon list
sermon-browser/single-sermon    // Single sermon display
sermon-browser/tag-cloud        // Tag cloud display
// ... and 9 more blocks
```

[Gutenberg Blocks](./gutenberg-blocks.md)

### REST API

RESTful API with seven controllers providing full CRUD operations for sermons, preachers, series, services, files, tags, and search, with rate limiting and authentication.

```php { .api }
// REST namespace: sermon-browser/v1
// Base URL: /wp-json/sermon-browser/v1/

GET    /sermons              // List sermons with filters
POST   /sermons              // Create sermon
GET    /sermons/{id}         // Get single sermon
PUT    /sermons/{id}         // Update sermon
DELETE /sermons/{id}         // Delete sermon
// ... and 40+ more endpoints
```

[REST API](./rest-api.md)

### Template Tag Functions

Over 100 public PHP functions for retrieving and displaying sermon data, including sermon retrieval, display helpers, pagination, Bible text integration, file handling, and podcast support.

```php { .api }
function sb_get_sermons($filter, $order, $page, $limit, $hide_empty): array;
function sb_display_sermons($options): void;
function sb_print_sermon_link($sermon, $echo): string|void;
function sb_print_filters($filter): void;
// ... and 100+ more functions
```

[Template Tag Functions](./template-tags.md)

### Facade Database API

Six facade classes providing static methods for database operations with type-safe interfaces for sermons, preachers, series, services, files, tags, and books.

```php { .api }
use SermonBrowser\Facades\Sermon;

$sermon = Sermon::find($id);
$sermons = Sermon::findAll($criteria, $limit, $offset);
$recent = Sermon::findRecent(10);
```

[Facades](./facades.md)

### Template System

Customizable template engine for rendering sermon lists and single sermon displays with tag-based templating, transient caching, and support for custom templates.

```php { .api }
use SermonBrowser\Templates\TemplateEngine;

$engine = new TemplateEngine();
$html = $engine->render('search', $data); // Multi-sermon list
$html = $engine->render('single', $data); // Single sermon
```

[Template System](./template-system.md)

### WordPress Hooks

Actions and filters for extending plugin functionality including core initialization hooks, frontend display hooks, admin panel hooks, and custom filter hooks.

```php { .api }
// Actions
add_action('init', 'sb_sermon_init');
add_action('wp_head', function() { /* headers */ });

// Filters
add_filter('wp_title', 'sb_page_title');
add_filter('widget_title', function($title) { /* filter */ });
```

[WordPress Hooks](./wordpress-hooks.md)

### Podcast Feed

RSS 2.0 podcast feed with iTunes extensions supporting custom filtering by preacher, series, or service, automatic enclosure metadata, and full podcast directory compatibility.

```php { .api }
// Podcast feed URLs
?podcast                    // All sermons
?podcast&preacher=ID        // By preacher
?podcast&series=ID          // By series
?podcast&service=ID         // By service
```

[Podcast Feed](./podcast.md)

## Database Schema

The plugin creates nine database tables with the WordPress table prefix followed by `sb_`:

- **sb_sermons**: Main sermon records with title, dates, Bible passages, descriptions
- **sb_preachers**: Preacher information with names, images, biographies
- **sb_series**: Sermon series with names, images, descriptions
- **sb_services**: Church services with names and times
- **sb_stuff**: Attached files, URLs, and embed codes linked to sermons
- **sb_tags**: Sermon topic tags
- **sb_books**: Bible books
- **sb_sermons_tags**: Many-to-many relationship between sermons and tags
- **sb_books_sermons**: Many-to-many relationship between sermons and books

## Security

The plugin includes comprehensive security features:

- **Input Sanitization**: All user input sanitized via `\SermonBrowser\Security\Sanitizer`
- **CSRF Protection**: Token-based CSRF protection for admin forms
- **SQL Injection Prevention**: Prepared statements using `$wpdb->prepare()`
- **XSS Prevention**: All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- **Rate Limiting**: REST API rate limits (60/min anonymous, 120/min authenticated)
- **Capability Checks**: WordPress capability verification for all privileged operations

## Internationalization

The plugin supports internationalization with text domain `sermon-browser` and includes translations for 9 languages. All strings use WordPress i18n functions (`__()`, `_e()`, `esc_html__()`).

## Constants

Key plugin constants defined in `\SermonBrowser\Constants`:

```php { .api }
const REST_NAMESPACE = 'sermon-browser/v1';
const CAP_MANAGE_SERMONS = 'edit_posts';
const RATE_LIMIT_ANON = 60;           // Requests per minute
const RATE_LIMIT_AUTH = 120;          // Requests per minute
const RATE_LIMIT_SEARCH_ANON = 20;    // Search requests per minute
const RATE_LIMIT_SEARCH_AUTH = 60;    // Search requests per minute
```
