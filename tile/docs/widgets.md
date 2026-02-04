# WordPress Widgets

Sermon Browser provides three WordPress widgets for displaying sermon content in sidebars and widget areas: Sermons Widget for recent sermon lists, Tag Cloud Widget for topic visualization, and Popular Widget for most-accessed content.

## Capabilities

### Sermons Widget

Display a list of recent sermons in widget areas with filtering options and customizable display fields.

```php { .api }
/**
 * Recent sermons widget
 *
 * Class: \SermonBrowser\Widgets\SermonsWidget
 * Extends: WP_Widget
 * Widget ID: sb_sermons
 */
class SermonsWidget extends WP_Widget {
    /**
     * Widget options:
     * - title (string): Widget title
     * - limit (int): Number of sermons to display (default: 5)
     * - preacher (int): Filter by preacher ID (0 = all)
     * - series (int): Filter by series ID (0 = all)
     * - service (int): Filter by service ID (0 = all)
     * - show_preacher (bool): Display preacher name (default: true)
     * - show_book (bool): Display Bible book (default: true)
     * - show_date (bool): Display sermon date (default: true)
     */
    public function widget($args, $instance): void;
    public function form($instance): void;
    public function update($new_instance, $old_instance): array;
}
```

**Usage Example (Programmatic):**

```php
// Register widget area in theme
register_sidebar([
    'name' => 'Sermon Sidebar',
    'id' => 'sermon-sidebar',
]);

// Widget can be added via Appearance > Widgets in admin
// Or programmatically:
the_widget('SermonBrowser\\Widgets\\SermonsWidget', [
    'title' => 'Recent Sermons',
    'limit' => 10,
    'show_preacher' => true,
    'show_date' => true,
]);
```

**Widget Configuration (Admin UI):**
- Title: Custom widget title
- Number to show: Integer (1-100)
- Filter by preacher: Dropdown of preachers
- Filter by series: Dropdown of series
- Filter by service: Dropdown of services
- Show preacher: Checkbox
- Show Bible book: Checkbox
- Show date: Checkbox

### Tag Cloud Widget

Display a tag cloud of sermon topics with font sizes based on usage frequency.

```php { .api }
/**
 * Sermon tag cloud widget
 *
 * Class: \SermonBrowser\Widgets\TagCloudWidget
 * Extends: WP_Widget
 * Widget ID: sb_tag_cloud
 */
class TagCloudWidget extends WP_Widget {
    /**
     * Widget options:
     * - title (string): Widget title
     */
    public function widget($args, $instance): void;
    public function form($instance): void;
    public function update($new_instance, $old_instance): array;
}
```

**Usage Example (Programmatic):**

```php
// Add tag cloud to sidebar
the_widget('SermonBrowser\\Widgets\\TagCloudWidget', [
    'title' => 'Sermon Topics',
]);
```

**Widget Configuration (Admin UI):**
- Title: Custom widget title

**Display Format:**
- Tags displayed with varying font sizes (12-24pt) based on frequency
- Tags are clickable links that filter sermons by tag
- More frequently used tags appear larger

### Popular Widget

Display most popular sermons, series, and/or preachers based on download counts and view statistics.

```php { .api }
/**
 * Most popular content widget
 *
 * Class: \SermonBrowser\Widgets\PopularWidget
 * Extends: WP_Widget
 * Widget ID: sb_popular
 */
class PopularWidget extends WP_Widget {
    /**
     * Widget options:
     * - title (string): Widget title
     * - limit (int): Number of items to display (default: 5)
     * - display_sermons (bool): Show popular sermons (default: true)
     * - display_series (bool): Show popular series (default: false)
     * - display_preachers (bool): Show popular preachers (default: false)
     */
    public function widget($args, $instance): void;
    public function form($instance): void;
    public function update($new_instance, $old_instance): array;
}
```

**Usage Example (Programmatic):**

```php
// Display popular sermons
the_widget('SermonBrowser\\Widgets\\PopularWidget', [
    'title' => 'Most Popular',
    'limit' => 10,
    'display_sermons' => true,
    'display_series' => true,
    'display_preachers' => false,
]);
```

**Widget Configuration (Admin UI):**
- Title: Custom widget title
- Number to show: Integer (1-100)
- Display popular sermons: Checkbox
- Display popular series: Checkbox
- Display popular preachers: Checkbox

**Popularity Calculation:**
- Sermons: Based on total download counts of attached files
- Series: Based on total download counts of all sermons in series
- Preachers: Based on total download counts of all sermons by preacher

## Widget Registration

All widgets are automatically registered on the `widgets_init` hook:

```php { .api }
/**
 * Register all Sermon Browser widgets
 *
 * Hooked to: widgets_init
 */
add_action('widgets_init', function() {
    register_widget('SermonBrowser\\Widgets\\SermonsWidget');
    register_widget('SermonBrowser\\Widgets\\TagCloudWidget');
    register_widget('SermonBrowser\\Widgets\\PopularWidget');
});
```

## Widget Areas

Widgets can be added to any widget area (sidebar, footer, etc.) registered by the active theme via:
- **Appearance > Widgets** in WordPress admin
- **Customizer > Widgets** section
- **Block-based widget editor** (WordPress 5.8+)

## Widget Template Functions

Widgets can also be displayed programmatically in theme templates using helper functions:

```php { .api }
/**
 * Display tag cloud widget content
 *
 * @param int $minfont Minimum font size in pixels (default: 12)
 * @param int $maxfont Maximum font size in pixels (default: 24)
 */
function sb_print_tag_clouds($minfont, $maxfont): void;

/**
 * Display most popular content
 * Uses default settings from PopularWidget
 */
function sb_print_most_popular(): void;
```

**Usage:**

```php
// In theme template
<aside class="sidebar">
    <h3>Sermon Topics</h3>
    <?php sb_print_tag_clouds(12, 24); ?>

    <h3>Most Popular</h3>
    <?php sb_print_most_popular(); ?>
</aside>
```

## Widget Styling

Widgets output standard WordPress widget markup:

```html
<div class="widget sermon-browser-widget">
    <h2 class="widget-title">Widget Title</h2>
    <div class="widget-content">
        <!-- Widget content -->
    </div>
</div>
```

Custom CSS can target these classes for styling:

```css
.sermon-browser-widget {
    /* Widget container styles */
}

.sermon-browser-widget .widget-title {
    /* Widget title styles */
}

.sermon-browser-widget .widget-content {
    /* Widget content styles */
}
```

## Widget Caching

Widget output is not cached by default. Each widget queries the database on every page load. For high-traffic sites, consider using a page caching plugin or implementing custom widget caching.

## Legacy Widget Support

The widgets are compatible with both the classic widget interface and the block-based widget editor introduced in WordPress 5.8. They appear in the widget picker as:
- "Sermon Browser: Sermons"
- "Sermon Browser: Tag Cloud"
- "Sermon Browser: Most Popular"
