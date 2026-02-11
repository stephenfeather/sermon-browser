# Custom Sermon Display Widget

Build a WordPress widget that displays filtered sermons using a custom template with Bible text integration.

## Requirements

You need to create a custom WordPress widget class that:

1. **Displays a configurable list of sermons** with the following widget settings:
   - Maximum number of sermons to display (default: 5)
   - Filter by preacher ID (optional)
   - Filter by series ID (optional)
   - Whether to hide sermons without audio files
   - Sort order: newest first or oldest first

2. **Renders sermons using a custom template** that displays:
   - Sermon title with hyperlink
   - Preacher name with hyperlink
   - Sermon date
   - First Bible passage reference
   - Bible text excerpt from the first passage (using KJV version, limited to first 100 characters)
   - Link to the first audio file if available

3. **Handles edge cases gracefully**:
   - Display a message when no sermons match the filter criteria
   - Handle sermons without Bible passages
   - Handle sermons without audio files

## Technical Specifications

- The widget must extend WordPress's `WP_Widget` class
- Use proper WordPress escaping functions for all output
- Register the widget in the appropriate WordPress hook
- The implementation should be in a single PHP file

## Implementation

[@generates](./src/sermon_widget.php)

## API

```php { #api }
<?php
/**
 * Custom Sermon Display Widget
 *
 * A WordPress widget that displays filtered sermons with Bible text.
 */

/**
 * Registers the sermon display widget with WordPress.
 * Should be called on the 'widgets_init' action hook.
 */
function register_custom_sermon_widget(): void;

/**
 * Custom Sermon Widget Class
 *
 * Extends WP_Widget to create a configurable sermon display widget.
 */
class Custom_Sermon_Widget extends WP_Widget {
    /**
     * Constructor - sets up widget with proper base ID, name, and description.
     */
    public function __construct();

    /**
     * Outputs the widget content on the frontend.
     *
     * @param array $args Display arguments including 'before_widget', 'after_widget', etc.
     * @param array $instance Widget settings configured by the user.
     */
    public function widget($args, $instance): void;

    /**
     * Outputs the widget settings form in the admin.
     *
     * @param array $instance Current widget settings.
     * @return string Form HTML output status.
     */
    public function form($instance): string;

    /**
     * Processes and saves widget settings.
     *
     * @param array $new_instance New settings submitted by the user.
     * @param array $old_instance Previous settings.
     * @return array Sanitized settings to save.
     */
    public function update($new_instance, $old_instance): array;
}
```

## Test Cases

### Widget Registration

- The widget is successfully registered when calling `register_custom_sermon_widget()` and appears in the WordPress widgets admin panel [@test](../test/widget_registration.test.php)

### Display Filtered Sermons

- Given 10 sermons in the database, when the widget is configured to display 5 sermons newest first, then exactly 5 sermons are displayed in descending date order [@test](../test/display_newest_sermons.test.php)

- Given 3 sermons from preacher ID 5 and 7 sermons from other preachers, when the widget is filtered by preacher ID 5, then only the 3 sermons from preacher ID 5 are displayed [@test](../test/filter_by_preacher.test.php)

- Given 15 sermons where 5 have no audio files, when the widget is configured to hide sermons without audio, then only the 10 sermons with audio files are displayed [@test](../test/hide_without_audio.test.php)

### Bible Text Integration

- Given a sermon with first passage "John 3:16-17", when the widget renders the sermon, then the Bible text for John 3:16-17 is displayed (KJV version) with the first 100 characters shown [@test](../test/display_bible_text.test.php)

- Given a sermon with no Bible passages, when the widget renders the sermon, then no Bible text section is displayed and no errors occur [@test](../test/handle_no_passages.test.php)

## Dependencies { .dependencies }

### sermon-browser { .dependency }

WordPress plugin for managing and displaying sermons. Provides sermon data retrieval, filtering, Bible integration, and URL generation capabilities.

[@satisfied-by](sermon-browser)
