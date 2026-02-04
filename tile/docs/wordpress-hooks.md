# WordPress Hooks

Sermon Browser provides numerous WordPress actions and filters for extending and customizing plugin functionality. These hooks enable theme developers and plugin authors to modify behavior, add features, and integrate with other WordPress systems.

## Actions

WordPress actions allow you to execute code at specific points during plugin execution.

## Capabilities

### Core Initialization Actions

```php { .api }
/**
 * Main plugin initialization
 *
 * Hook: init
 * Priority: 10
 * Function: sb_sermon_init()
 *
 * Fires on WordPress 'init' action
 * Registers shortcodes, post types, taxonomies, and AJAX handlers
 */
add_action('init', 'sb_sermon_init');

/**
 * Early request interception
 *
 * Hook: plugins_loaded
 * Priority: 10
 * Function: sb_hijack()
 *
 * Handles downloads, AJAX requests, and CSS delivery
 * Runs before WordPress fully loads
 */
add_action('plugins_loaded', 'sb_hijack');

/**
 * Widget registration
 *
 * Hook: widgets_init
 * Priority: 10
 *
 * Registers all Sermon Browser widgets
 */
add_action('widgets_init', function() {
    register_widget('SermonBrowser\\Widgets\\SermonsWidget');
    register_widget('SermonBrowser\\Widgets\\TagCloudWidget');
    register_widget('SermonBrowser\\Widgets\\PopularWidget');
});

/**
 * REST API initialization
 *
 * Hook: rest_api_init
 * Priority: 10
 *
 * Registers REST API routes and controllers
 */
add_action('rest_api_init', [\SermonBrowser\REST\RestAPI::class, 'registerRoutes']);

/**
 * Block registration
 *
 * Hook: init
 * Priority: 10
 *
 * Registers Gutenberg blocks
 */
add_action('init', [\SermonBrowser\Blocks\BlockRegistry::class, 'registerAll']);
```

### Frontend Hooks

```php { .api }
/**
 * Add headers and styles
 *
 * Hook: wp_head
 * Priority: 10
 *
 * Outputs custom CSS, meta tags, and other head content
 */
add_action('wp_head', function() {
    // Custom CSS
    // OpenGraph tags for podcasts
    // RSS feed links
});

/**
 * Add admin bar menu
 *
 * Hook: admin_bar_menu
 * Priority: 100
 *
 * Adds "Sermons" link to WordPress admin bar
 */
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (current_user_can('edit_posts')) {
        $wp_admin_bar->add_menu([
            'id' => 'sermon-browser',
            'title' => 'Sermons',
            'href' => admin_url('admin.php?page=sermon-browser')
        ]);
    }
}, 100);

/**
 * Footer statistics
 *
 * Hook: wp_footer
 * Priority: 999
 *
 * Outputs query statistics if SAVEQUERIES is enabled
 */
add_action('wp_footer', function() {
    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        // Output query count and time
    }
}, 999);
```

### Admin Hooks

```php { .api }
/**
 * Add admin menu pages
 *
 * Hook: admin_menu
 * Priority: 10
 *
 * Registers admin menu items for sermon management
 */
add_action('admin_menu', function() {
    add_menu_page(
        'Sermons',                    // Page title
        'Sermons',                    // Menu title
        'edit_posts',                 // Capability
        'sermon-browser',             // Menu slug
        'sb_render_admin_page',       // Callback
        'dashicons-microphone',       // Icon
        25                            // Position
    );
    // Add submenu pages...
});

/**
 * Enqueue admin assets
 *
 * Hook: admin_enqueue_scripts
 * Priority: 10
 *
 * Loads admin JavaScript and CSS
 */
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'sermon-browser') !== false) {
        wp_enqueue_style('sermon-browser-admin', plugins_url('assets/css/admin.css', __FILE__));
        wp_enqueue_script('sermon-browser-admin', plugins_url('assets/js/admin.js', __FILE__));
    }
});

/**
 * Add help tabs
 *
 * Hook: current_screen
 * Priority: 10
 *
 * Adds contextual help tabs to admin pages
 */
add_action('current_screen', function($screen) {
    if (strpos($screen->id, 'sermon-browser') !== false) {
        $screen->add_help_tab([
            'id' => 'sb-help',
            'title' => 'Help',
            'content' => '<p>Help content...</p>'
        ]);
    }
});

/**
 * Admin footer statistics
 *
 * Hook: admin_footer
 * Priority: 10
 *
 * Outputs query statistics in admin if SAVEQUERIES enabled
 */
add_action('admin_footer', function() {
    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        // Output query stats
    }
});

/**
 * Display admin notices
 *
 * Hook: admin_notices
 * Priority: 10
 *
 * Shows template migration results and other notices
 */
add_action('admin_notices', function() {
    // Show migration results
    // Show update notices
    // Show error messages
});

/**
 * Initialize admin settings
 *
 * Hook: admin_init
 * Priority: 10
 *
 * Registers settings, migrates widget settings
 */
add_action('admin_init', function() {
    // Register settings
    // Migrate legacy data
});
```

### Content Hooks

```php { .api }
/**
 * Update podcast URL on sermon save
 *
 * Hook: save_post
 * Priority: 10
 * Parameters: $post_id, $post, $update
 *
 * Updates cached podcast URL when sermons change
 */
add_action('save_post', function($post_id, $post, $update) {
    // Clear podcast feed cache
    delete_transient('sb_podcast_url');
}, 10, 3);
```

### Activation Hook

```php { .api }
/**
 * Plugin activation
 *
 * Hook: register_activation_hook
 *
 * Runs template migration and initial setup
 */
register_activation_hook(__FILE__, function() {
    // Migrate templates from legacy format
    $manager = new \SermonBrowser\Templates\TemplateManager();
    $manager->migrateFromLegacy();

    // Create default options
    // Set up database tables if needed
});
```

## Filters

WordPress filters allow you to modify data before it's used or displayed.

### Core Filters

```php { .api }
/**
 * Modify page title
 *
 * Filter: wp_title
 * Priority: 10
 * Function: sb_page_title()
 * Parameters: $title, $sep
 *
 * Modifies page title for sermon pages
 */
add_filter('wp_title', 'sb_page_title', 10, 2);

// Usage example
function sb_page_title($title, $sep) {
    if (sb_display_front_end()) {
        $sermon = sb_get_single_sermon($_GET['id'] ?? 0);
        if ($sermon) {
            return $sermon->title . " $sep " . get_bloginfo('name');
        }
    }
    return $title;
}

/**
 * Add sermon count to dashboard
 *
 * Filter: dashboard_glance_items
 * Priority: 10
 * Parameters: $items
 *
 * Adds sermon count to "At a Glance" dashboard widget
 */
add_filter('dashboard_glance_items', function($items) {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sb_sermons");
    $items[] = sprintf(
        '<a href="%s">%d %s</a>',
        admin_url('admin.php?page=sermon-browser'),
        $count,
        _n('Sermon', 'Sermons', $count, 'sermon-browser')
    );
    return $items;
});

/**
 * Filter widget titles
 *
 * Filter: widget_title
 * Priority: 10
 * Parameters: $title, $instance, $id_base
 *
 * Allows modification of widget titles
 */
add_filter('widget_title', function($title, $instance, $id_base) {
    // Modify widget titles if needed
    return $title;
}, 10, 3);
```

## Extension Points

The plugin primarily uses WordPress's built-in hooks for extension. Custom plugin-specific action/filter hooks were not identified in the codebase analysis. Developers should use the standard WordPress hooks documented above for extending plugin functionality.

## Extension Examples

### Modify Page Title for Sermons

```php
// Customize sermon page titles
add_filter('wp_title', function($title, $sep) {
    if (sb_display_front_end() && isset($_GET['id'])) {
        $sermon = sb_get_single_sermon($_GET['id']);
        if ($sermon) {
            return $sermon->title . " $sep " . get_bloginfo('name');
        }
    }
    return $title;
}, 10, 2);
```

### Add Sermons to Dashboard At-a-Glance

```php
// Add sermon statistics to dashboard
add_filter('dashboard_glance_items', function($items) {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sb_sermons");
    $items[] = sprintf(
        '<a href="%s" class="sermon-count">%d %s</a>',
        admin_url('admin.php?page=sermon-browser'),
        $count,
        _n('Sermon', 'Sermons', $count, 'sermon-browser')
    );
    return $items;
});
```

### Custom Widget Title Formatting

```php
// Modify sermon widget titles
add_filter('widget_title', function($title, $instance, $id_base) {
    if (strpos($id_base, 'sb_') === 0) {
        // Add icon to sermon widget titles
        return '<span class="dashicons dashicons-microphone"></span> ' . $title;
    }
    return $title;
}, 10, 3);
```

## Hook Priority

Use priority values to control execution order:

```php
// Early execution (priority 1-9)
add_action('init', 'my_early_function', 5);

// Normal execution (priority 10, default)
add_action('init', 'my_normal_function');

// Late execution (priority 11+)
add_action('init', 'my_late_function', 100);
```

## Removing Hooks

Remove plugin hooks if needed:

```php
// Remove action
remove_action('wp_head', 'sb_add_head_content', 10);

// Remove filter
remove_filter('wp_title', 'sb_page_title', 10);
```

## Best Practices

1. **Always check exists**: Use `has_action()` and `has_filter()` before adding
2. **Use appropriate priority**: Default 10 works for most cases
3. **Clean up**: Remove hooks when no longer needed
4. **Document custom hooks**: Add PHPDoc comments
5. **Test thoroughly**: Hooks can have side effects
6. **Performance**: Avoid heavy operations in frequently-called hooks
