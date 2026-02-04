# Template Tag Functions

Public PHP template tag functions that enable developers to display and manipulate sermon data in WordPress templates. These functions provide the foundational API for custom theme integration, allowing direct control over sermon display, filtering, navigation, and data retrieval.

All template tag functions are globally available after plugin initialization and are defined in the main `sermon.php` file.

## Basic Usage

```php
<?php
// Get and display sermons in a template
$sermons = sb_get_sermons([], 'desc', 1, 10, false);
foreach ($sermons as $sermon) {
    echo '<h2>' . esc_html($sermon->title) . '</h2>';
    echo '<p>By: ' . esc_html($sermon->preacher_name) . '</p>';
    sb_print_sermon_link($sermon, true);
}

// Display single sermon with all details
$sermon = sb_get_single_sermon(123);
if ($sermon) {
    echo '<h1>' . esc_html($sermon->title) . '</h1>';
    sb_print_bible_passage($sermon->bible_passage, $sermon->bible_passage_end);
    sb_print_preacher_link($sermon);
    sb_print_series_link($sermon);

    // Display sermon files
    $files = sb_get_stuff($sermon, false);
    foreach ($files as $file) {
        sb_print_url($file->stuff);
    }
}

// Display complete sermon listing with pagination and filters
sb_display_sermons([
    'filter' => 'dropdown',
    'limit' => 20,
    'preacher' => 5,
    'series' => 10
]);
```

## Capabilities

### Sermon Retrieval Functions

Core functions for retrieving sermon data from the database with filtering, pagination, and relation loading.

```php { .api }
function sb_get_sermons($filter, $order, $page, $limit, $hide_empty)
```

Get multiple sermons with filtering and pagination.

**Parameters:**
- `$filter` (array): Filter criteria containing keys like 'preacher', 'series', 'service', 'book', 'tag', 'date', 'enddate', 'title'
- `$order` (string): Sort order - "asc" or "desc"
- `$page` (int): Page number for pagination
- `$limit` (int): Number of sermons per page
- `$hide_empty` (bool): Hide sermons without files

**Returns:** array - Array of sermon objects

```php { .api }
function sb_get_single_sermon($id)
```

Get single sermon by ID with all related data (files, tags, books).

**Parameters:**
- `$id` (int): Sermon ID

**Returns:** object|null - Sermon object with all relations or null if not found

```php { .api }
function sb_get_stuff($sermon, $mp3_only)
```

Get sermon attachments (files, URLs, embed codes).

**Parameters:**
- `$sermon` (object): Sermon object
- `$mp3_only` (bool): Return only MP3 files if true

**Returns:** array - Array of file/URL/code objects with properties: id, sermon_id, stuff, stuff_type, download_count

```php { .api }
function sb_sermon_stats($sermonid)
```

Get download statistics for a sermon.

**Parameters:**
- `$sermonid` (int): Sermon ID

**Returns:** object - Object with download statistics including total downloads and per-file counts

**Example:**
```php
// Get recent sermons from a specific preacher
$sermons = sb_get_sermons(
    ['preacher' => 5, 'series' => 10],
    'desc',
    1,
    10,
    false
);

// Get single sermon with all details
$sermon = sb_get_single_sermon(123);
if ($sermon) {
    echo $sermon->title; // "The Gospel of Grace"
    echo $sermon->preacher_name; // "John Smith"

    // Get all attachments
    $files = sb_get_stuff($sermon, false);

    // Get only MP3 files
    $mp3s = sb_get_stuff($sermon, true);

    // Get download stats
    $stats = sb_sermon_stats($sermon->id);
    echo "Total Downloads: " . $stats->total;
}
```

### Display Functions

High-level functions for displaying sermons with complete formatting, filtering, and pagination.

```php { .api }
function sb_display_sermons($options)
```

Display sermons in templates with all formatting, filtering, and pagination controls.

**Parameters:**
- `$options` (array): Display options containing:
  - `filter` (string): Filter UI type - "dropdown", "oneclick", "none"
  - `filterhide` (bool): Hide filter UI
  - `id` (mixed): Sermon ID or "latest"
  - `preacher` (int): Filter by preacher ID
  - `series` (int): Filter by series ID
  - `book` (string): Filter by Bible book name
  - `service` (int): Filter by service ID
  - `date` (string): Filter by date (YYYY-MM-DD)
  - `enddate` (string): End date for range
  - `tag` (string): Filter by tag slug
  - `title` (string): Search by title text
  - `limit` (int): Number of sermons to display
  - `dir` (string): Sort direction - "asc", "desc"

**Returns:** void - Outputs HTML directly

```php { .api }
function sb_print_sermon_link($sermon, $echo)
```

Print or return sermon detail page URL.

**Parameters:**
- `$sermon` (object): Sermon object
- `$echo` (bool): Echo output if true, return if false

**Returns:** string|void - URL string if $echo is false, otherwise outputs HTML

```php { .api }
function sb_print_preacher_link($sermon)
```

Print preacher filter URL as clickable link.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs HTML link

```php { .api }
function sb_print_series_link($sermon)
```

Print series filter URL as clickable link.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs HTML link

```php { .api }
function sb_print_service_link($sermon)
```

Print service filter URL as clickable link.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs HTML link

```php { .api }
function sb_print_tags($tags)
```

Print tag list with clickable links to tag filter pages.

**Parameters:**
- `$tags` (array): Array of tag objects

**Returns:** void - Outputs HTML

```php { .api }
function sb_print_tag_clouds($minfont, $maxfont)
```

Print tag cloud with weighted font sizes based on usage.

**Parameters:**
- `$minfont` (int): Minimum font size in pixels
- `$maxfont` (int): Maximum font size in pixels

**Returns:** void - Outputs HTML tag cloud

```php { .api }
function sb_print_most_popular()
```

Print most popular sermons, series, and preachers widget.

**Returns:** void - Outputs HTML widget

**Example:**
```php
<!-- Display filtered sermon list -->
<?php
sb_display_sermons([
    'filter' => 'dropdown',
    'limit' => 20,
    'preacher' => 5,
    'dir' => 'desc'
]);
?>

<!-- Display sermon with links -->
<?php
$sermon = sb_get_single_sermon(123);
echo '<h2>' . esc_html($sermon->title) . '</h2>';
sb_print_preacher_link($sermon); // Links to preacher's sermons
sb_print_series_link($sermon);   // Links to series page
sb_print_service_link($sermon);  // Links to service page
sb_print_tags($sermon->tags);    // Display tags with links
?>

<!-- Display tag cloud in sidebar -->
<?php sb_print_tag_clouds(12, 24); ?>

<!-- Display popular content -->
<?php sb_print_most_popular(); ?>
```

### Pagination Functions

Functions for displaying pagination controls for sermon listings.

```php { .api }
function sb_print_next_page_link($limit)
```

Print next page navigation link.

**Parameters:**
- `$limit` (int): Items per page

**Returns:** void - Outputs HTML link or disabled state

```php { .api }
function sb_print_prev_page_link($limit)
```

Print previous page navigation link.

**Parameters:**
- `$limit` (int): Items per page

**Returns:** void - Outputs HTML link or disabled state

**Example:**
```php
<!-- Pagination controls -->
<div class="sermon-pagination">
    <?php sb_print_prev_page_link(20); ?>
    <?php sb_print_next_page_link(20); ?>
</div>
```

### File Display Functions

Functions for displaying sermon files, URLs, and embed codes.

```php { .api }
function sb_print_url($url)
```

Print file download link with icon and download tracking.

**Parameters:**
- `$url` (string): File URL or path

**Returns:** void - Outputs HTML link

```php { .api }
function sb_print_url_link($url)
```

Print external URL link with icon.

**Parameters:**
- `$url` (string): External URL

**Returns:** void - Outputs HTML link

```php { .api }
function sb_print_code($code)
```

Print base64 decoded embed code (video/audio players).

**Parameters:**
- `$code` (string): Base64 encoded embed code

**Returns:** void - Outputs decoded HTML

```php { .api }
function sb_first_mp3($sermon, $stats)
```

Get first MP3 file URL from sermon.

**Parameters:**
- `$sermon` (object): Sermon object
- `$stats` (bool): Include download stats parameter in URL

**Returns:** string - MP3 file URL or empty string if none found

**Example:**
```php
<?php
$sermon = sb_get_single_sermon(123);
$files = sb_get_stuff($sermon, false);

// Display all files
foreach ($files as $file) {
    if ($file->stuff_type === 'file') {
        sb_print_url($file->stuff);
    } elseif ($file->stuff_type === 'url') {
        sb_print_url_link($file->stuff);
    } elseif ($file->stuff_type === 'code') {
        sb_print_code($file->stuff);
    }
}

// Get first MP3 for audio player
$mp3_url = sb_first_mp3($sermon, true);
if ($mp3_url) {
    echo '<audio src="' . esc_url($mp3_url) . '" controls></audio>';
}
?>
```

### Bible Text Functions

Functions for displaying Bible references, passages, and retrieving Scripture text from online services.

```php { .api }
function sb_get_bible_books()
```

Get list of all Bible books.

**Returns:** array - Array of Bible book objects with id and book_name properties

```php { .api }
function sb_tidy_reference($start, $end, $add_link)
```

Format Bible reference into readable format (e.g., "John 3:16-18").

**Parameters:**
- `$start` (string): Start reference
- `$end` (string): End reference
- `$add_link` (bool): Add links to book names

**Returns:** string - Formatted reference

```php { .api }
function sb_get_books($start, $end)
```

Get Bible book names with links.

**Parameters:**
- `$start` (string): Start reference
- `$end` (string): End reference

**Returns:** string - HTML with book names and links

```php { .api }
function sb_get_book_link($book_name)
```

Get book filter link URL.

**Parameters:**
- `$book_name` (string): Bible book name

**Returns:** string - Filter URL for book

```php { .api }
function sb_print_bible_passage($start, $end)
```

Print formatted Bible passage reference with links.

**Parameters:**
- `$start` (string): Start reference
- `$end` (string): End reference

**Returns:** void - Outputs HTML

```php { .api }
function sb_add_bible_text($start, $end, $version)
```

Add Bible text to page from online sources.

**Parameters:**
- `$start` (string): Start reference (e.g., "John 3:16")
- `$end` (string): End reference (e.g., "John 3:18")
- `$version` (string): Bible version code ("esv", "net", "kjv", "niv", etc.)

**Returns:** void - Outputs HTML with Bible text

```php { .api }
function sb_add_esv_text($start, $end)
```

Add ESV (English Standard Version) Bible text.

**Parameters:**
- `$start` (string): Start reference
- `$end` (string): End reference

**Returns:** void - Outputs HTML

```php { .api }
function sb_add_net_text($start, $end)
```

Add NET (New English Translation) Bible text with study notes.

**Parameters:**
- `$start` (string): Start reference
- `$end` (string): End reference

**Returns:** void - Outputs HTML

```php { .api }
function sb_add_other_bibles($start, $end, $version)
```

Add other Bible versions (KJV, NIV, NASB, etc.) via BibleGateway.

**Parameters:**
- `$start` (string): Start reference
- `$end` (string): End reference
- `$version` (string): Bible version code

**Returns:** void - Outputs HTML

**Example:**
```php
<?php
$sermon = sb_get_single_sermon(123);

// Display Bible reference
sb_print_bible_passage($sermon->bible_passage, $sermon->bible_passage_end);
// Output: "John 3:16-18" with links

// Display formatted reference
$ref = sb_tidy_reference($sermon->bible_passage, $sermon->bible_passage_end, true);
echo $ref; // "John 3:16-18"

// Add Bible text in different versions
sb_add_esv_text('John 3:16', 'John 3:18');
sb_add_net_text('John 3:16', 'John 3:18');
sb_add_other_bibles('John 3:16', 'John 3:18', 'NIV');

// Get all Bible books
$books = sb_get_bible_books();
foreach ($books as $book) {
    $link = sb_get_book_link($book->book_name);
    echo '<a href="' . esc_url($link) . '">' . esc_html($book->book_name) . '</a>';
}
?>
```

### Helper Functions

Utility functions for formatting, URL building, and accessing sermon metadata.

```php { .api }
function sb_formatted_date($sermon)
```

Format sermon date according to WordPress date settings.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** string - Formatted date string

```php { .api }
function sb_build_url($arr, $clear)
```

Build filter URL with query parameters.

**Parameters:**
- `$arr` (array): Query parameters as key-value pairs
- `$clear` (bool): Clear existing parameters if true

**Returns:** string - Complete URL with parameters

```php { .api }
function sb_get_tag_link($tag)
```

Get tag filter link URL.

**Parameters:**
- `$tag` (object): Tag object with tag_name property

**Returns:** string - Filter URL for tag

```php { .api }
function sb_edit_link($id)
```

Display edit sermon link if user has permission.

**Parameters:**
- `$id` (int): Sermon ID

**Returns:** void - Outputs HTML link if user can edit

```php { .api }
function sb_print_preacher_description($sermon)
```

Print preacher biography/description.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs HTML

```php { .api }
function sb_print_preacher_image($sermon)
```

Print preacher image/photo.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs HTML img tag

**Example:**
```php
<?php
$sermon = sb_get_single_sermon(123);

// Display formatted date
echo sb_formatted_date($sermon); // "January 15, 2024"

// Build filter URLs
$preacher_url = sb_build_url(['preacher' => 5], false);
$series_url = sb_build_url(['series' => 10, 'service' => 3], true);

// Display tag links
foreach ($sermon->tags as $tag) {
    $link = sb_get_tag_link($tag);
    echo '<a href="' . esc_url($link) . '">' . esc_html($tag->tag_name) . '</a>';
}

// Display edit link (only if user has permission)
sb_edit_link($sermon->id);

// Display preacher info
sb_print_preacher_image($sermon);
sb_print_preacher_description($sermon);
?>
```

### Navigation Functions

Functions for navigating between sermons and displaying related sermon links.

```php { .api }
function sb_print_next_sermon_link($sermon)
```

Display link to next sermon (chronologically).

**Parameters:**
- `$sermon` (object): Current sermon object

**Returns:** void - Outputs HTML link or nothing if no next sermon

```php { .api }
function sb_print_prev_sermon_link($sermon)
```

Display link to previous sermon (chronologically).

**Parameters:**
- `$sermon` (object): Current sermon object

**Returns:** void - Outputs HTML link or nothing if no previous sermon

```php { .api }
function sb_print_sameday_sermon_link($sermon)
```

Display links to sermons preached on the same day.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs HTML links

**Example:**
```php
<!-- Single sermon navigation -->
<?php
$sermon = sb_get_single_sermon(123);
?>
<div class="sermon-navigation">
    <div class="prev">
        <?php sb_print_prev_sermon_link($sermon); ?>
    </div>
    <div class="next">
        <?php sb_print_next_sermon_link($sermon); ?>
    </div>
</div>

<!-- Related sermons from same day -->
<?php sb_print_sameday_sermon_link($sermon); ?>
```

### Filter Display Functions

Functions for displaying filter interface controls (dropdowns, one-click filters).

```php { .api }
function sb_print_filters($filter)
```

Display complete filter interface (dropdown or oneclick style).

**Parameters:**
- `$filter` (string): Filter type - "dropdown" for select menus, "oneclick" for clickable links

**Returns:** void - Outputs HTML filter interface

```php { .api }
function sb_print_filter_line($id, $results, $filter, $display, $max_num)
```

Print individual filter line for a specific filter type.

**Parameters:**
- `$id` (int): Current filter item ID (if filtered)
- `$results` (array): Array of filter items
- `$filter` (string): Filter type ("preacher", "series", "service", "book", "tag")
- `$display` (string): Display format ("dropdown" or "oneclick")
- `$max_num` (int): Maximum items to show before truncating

**Returns:** void - Outputs HTML

```php { .api }
function sb_print_date_filter_line($dates)
```

Print date filter dropdown control.

**Parameters:**
- `$dates` (array): Array of available dates

**Returns:** void - Outputs HTML select element

```php { .api }
function sb_url_minus_parameter($param1, $param2)
```

Get current URL with specified parameters removed.

**Parameters:**
- `$param1` (string): First parameter to remove
- `$param2` (string|null): Second parameter to remove (optional)

**Returns:** string - URL without specified parameters

**Example:**
```php
<!-- Display dropdown filters -->
<?php sb_print_filters('dropdown'); ?>

<!-- Display one-click filters -->
<?php sb_print_filters('oneclick'); ?>

<!-- Custom filter implementation -->
<?php
$preachers = [/* array of preacher objects */];
sb_print_filter_line(
    $current_preacher_id,
    $preachers,
    'preacher',
    'dropdown',
    50
);

// Remove filter parameters
$clear_url = sb_url_minus_parameter('preacher', 'series');
echo '<a href="' . esc_url($clear_url) . '">Clear Filters</a>';
?>
```

### Options Functions

Functions for accessing and updating plugin options stored in WordPress database.

```php { .api }
function sb_get_option($type)
```

Get plugin option value from database.

**Parameters:**
- `$type` (string): Option key (e.g., "page_id", "preacher_label", "series_label", "service_label", "use_css", "template_search", "template_single")

**Returns:** mixed - Option value (type varies by option)

```php { .api }
function sb_update_option($type, $val)
```

Update plugin option in database.

**Parameters:**
- `$type` (string): Option key
- `$val` (mixed): Option value to save

**Returns:** void

```php { .api }
function sb_get_default($default_type)
```

Get default value for an option.

**Parameters:**
- `$default_type` (string): Default type key

**Returns:** mixed - Default value

**Example:**
```php
<?php
// Get option values
$page_id = sb_get_option('page_id');
$preacher_label = sb_get_option('preacher_label'); // "Preacher"
$use_css = sb_get_option('use_css'); // true/false

// Update option
sb_update_option('preacher_label', 'Speaker');
sb_update_option('series_label', 'Message Series');

// Get default values
$default_template = sb_get_default('template_search');
?>
```

### Page/URL Functions

Functions for retrieving sermon page URLs and checking display context.

```php { .api }
function sb_display_url()
```

Get main sermons page URL.

**Returns:** string - URL to sermons page

```php { .api }
function sb_get_page_id()
```

Get sermons page ID from plugin options.

**Returns:** int - WordPress page ID for sermons

```php { .api }
function sb_display_front_end()
```

Check if currently displaying sermons page on frontend.

**Returns:** bool - True if on sermons page, false otherwise

```php { .api }
function sb_query_char($return_entity)
```

Get appropriate query string separator character (? or &).

**Parameters:**
- `$return_entity` (bool): Return HTML entity (&amp;) if true, & if false

**Returns:** string - "?" or "&" (or "&amp;")

```php { .api }
function sb_podcast_url()
```

Get podcast feed URL.

**Returns:** string - URL to podcast RSS feed

**Example:**
```php
<?php
// Get sermons page URL
$sermons_url = sb_display_url();
echo '<a href="' . esc_url($sermons_url) . '">View All Sermons</a>';

// Get page ID
$page_id = sb_get_page_id();
$page = get_post($page_id);

// Check if on sermons page
if (sb_display_front_end()) {
    // Add custom CSS or scripts
}

// Build query URL
$base_url = sb_display_url();
$separator = sb_query_char(true);
$filter_url = $base_url . $separator . 'preacher=5&series=10';

// Get podcast URL
$podcast = sb_podcast_url();
echo '<a href="' . esc_url($podcast) . '">Subscribe to Podcast</a>';
?>
```

### Podcast Functions

Functions for generating podcast feed data including dates, file sizes, durations, and MIME types.

```php { .api }
function sb_print_iso_date($sermon)
```

Format date in ISO format for podcast RSS feed.

**Parameters:**
- `$sermon` (object): Sermon object

**Returns:** void - Outputs ISO date string (RFC 2822 format)

```php { .api }
function sb_media_size($media_name, $media_type)
```

Get file size in bytes for podcast enclosure.

**Parameters:**
- `$media_name` (string): File path or URL
- `$media_type` (string): Media type ("file", "url", "code")

**Returns:** int - File size in bytes

```php { .api }
function sb_mp3_duration($media_name, $media_type)
```

Get MP3 file duration for podcast feed.

**Parameters:**
- `$media_name` (string): File path or URL
- `$media_type` (string): Media type ("file", "url")

**Returns:** string - Duration in HH:MM:SS format

```php { .api }
function sb_xml_entity_encode($string)
```

Encode string for XML/RSS feed to prevent parsing errors.

**Parameters:**
- `$string` (string): String to encode

**Returns:** string - XML-safe encoded string

```php { .api }
function sb_podcast_file_url($media_name, $media_type)
```

Get full URL for podcast file enclosure.

**Parameters:**
- `$media_name` (string): File path or URL
- `$media_type` (string): Media type ("file", "url")

**Returns:** string - Full file URL

```php { .api }
function sb_mime_type($media_name)
```

Get MIME type for file based on extension.

**Parameters:**
- `$media_name` (string): File path or URL

**Returns:** string - MIME type (e.g., "audio/mpeg", "video/mp4")

**Example:**
```php
<?php
// Generate podcast enclosure data
$sermon = sb_get_single_sermon(123);
$mp3_path = sb_first_mp3($sermon, false);

if ($mp3_path) {
    $file_url = sb_podcast_file_url($mp3_path, 'file');
    $file_size = sb_media_size($mp3_path, 'file');
    $duration = sb_mp3_duration($mp3_path, 'file');
    $mime_type = sb_mime_type($mp3_path);

    echo '<enclosure
        url="' . esc_url($file_url) . '"
        length="' . esc_attr($file_size) . '"
        type="' . esc_attr($mime_type) . '" />';

    echo '<itunes:duration>' . esc_html($duration) . '</itunes:duration>';
}

// Format date for RSS
sb_print_iso_date($sermon); // Outputs: "Wed, 15 Jan 2024 10:30:00 +0000"

// Encode strings for XML
$title = sb_xml_entity_encode($sermon->title);
$description = sb_xml_entity_encode($sermon->description);
?>
```

### Utility Functions

Low-level utility functions for file handling, path sanitization, and download tracking.

```php { .api }
function sb_default_time($service)
```

Get default time for a service.

**Parameters:**
- `$service` (int): Service ID

**Returns:** string - Time in HH:MM format (e.g., "10:30")

```php { .api }
function sb_increase_download_count($stuff_name)
```

Increment download counter for a file.

**Parameters:**
- `$stuff_name` (string): File name or path

**Returns:** void

```php { .api }
function sb_mkdir($pathname, $mode)
```

Create directory recursively with proper permissions.

**Parameters:**
- `$pathname` (string): Directory path to create
- `$mode` (int): Unix permissions mode (e.g., 0755)

**Returns:** bool - True on success, false on failure

```php { .api }
function sb_sanitise_path($path)
```

Sanitize file paths for Windows compatibility.

**Parameters:**
- `$path` (string): Path to sanitize

**Returns:** string - Sanitized path with forward slashes

```php { .api }
function sb_output_file($filename)
```

Output file contents in chunks for download with proper headers.

**Parameters:**
- `$filename` (string): Full file path

**Returns:** void - Outputs file data directly to browser

**Example:**
```php
<?php
// Get default service time
$default_time = sb_default_time(1); // "10:30"

// Track downloads
sb_increase_download_count('sermon-2024-01-15.mp3');

// Create upload directory
$upload_path = wp_upload_dir()['basedir'] . '/sermons/2024/';
sb_mkdir($upload_path, 0755);

// Sanitize paths
$user_path = $_GET['file'];
$safe_path = sb_sanitise_path($user_path);

// Output file for download (use in download handler)
if (file_exists($safe_path)) {
    sb_increase_download_count(basename($safe_path));
    sb_output_file($safe_path);
    exit;
}
?>
```

## Integration Examples

### Custom Theme Template

```php
<?php
/**
 * Template Name: Sermons Archive
 */
get_header();
?>

<div class="sermons-page">
    <h1>Our Sermons</h1>

    <!-- Display filters -->
    <?php sb_print_filters('dropdown'); ?>

    <!-- Get and display sermons -->
    <?php
    $filter = [];
    if (isset($_GET['preacher'])) {
        $filter['preacher'] = intval($_GET['preacher']);
    }
    if (isset($_GET['series'])) {
        $filter['series'] = intval($_GET['series']);
    }

    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $sermons = sb_get_sermons($filter, 'desc', $page, 20, false);

    foreach ($sermons as $sermon) {
        ?>
        <article class="sermon-item">
            <h2>
                <a href="<?php echo esc_url(sb_print_sermon_link($sermon, false)); ?>">
                    <?php echo esc_html($sermon->title); ?>
                </a>
            </h2>

            <div class="sermon-meta">
                <span class="date"><?php echo sb_formatted_date($sermon); ?></span>
                <span class="preacher">
                    Preached by: <?php sb_print_preacher_link($sermon); ?>
                </span>
                <?php if ($sermon->series_name): ?>
                    <span class="series">
                        Series: <?php sb_print_series_link($sermon); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($sermon->bible_passage): ?>
                <div class="passage">
                    <?php sb_print_bible_passage($sermon->bible_passage, $sermon->bible_passage_end); ?>
                </div>
            <?php endif; ?>

            <?php if ($sermon->description): ?>
                <div class="description">
                    <?php echo wp_kses_post($sermon->description); ?>
                </div>
            <?php endif; ?>

            <div class="sermon-files">
                <?php
                $files = sb_get_stuff($sermon, false);
                foreach ($files as $file) {
                    if ($file->stuff_type === 'file') {
                        sb_print_url($file->stuff);
                    }
                }
                ?>
            </div>
        </article>
        <?php
    }
    ?>

    <!-- Pagination -->
    <div class="pagination">
        <?php sb_print_prev_page_link(20); ?>
        <?php sb_print_next_page_link(20); ?>
    </div>
</div>

<?php get_footer(); ?>
```

### Single Sermon Template

```php
<?php
/**
 * Template Name: Single Sermon
 */
get_header();

$sermon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sermon = sb_get_single_sermon($sermon_id);

if (!$sermon) {
    echo '<p>Sermon not found.</p>';
    get_footer();
    exit;
}
?>

<article class="single-sermon">
    <header class="sermon-header">
        <h1><?php echo esc_html($sermon->title); ?></h1>

        <div class="sermon-meta">
            <div class="meta-item">
                <strong>Date:</strong> <?php echo sb_formatted_date($sermon); ?>
            </div>

            <div class="meta-item">
                <strong>Preacher:</strong> <?php sb_print_preacher_link($sermon); ?>
            </div>

            <?php if ($sermon->series_name): ?>
                <div class="meta-item">
                    <strong>Series:</strong> <?php sb_print_series_link($sermon); ?>
                </div>
            <?php endif; ?>

            <?php if ($sermon->service_name): ?>
                <div class="meta-item">
                    <strong>Service:</strong> <?php sb_print_service_link($sermon); ?>
                </div>
            <?php endif; ?>

            <?php if ($sermon->bible_passage): ?>
                <div class="meta-item">
                    <strong>Bible Passage:</strong>
                    <?php sb_print_bible_passage($sermon->bible_passage, $sermon->bible_passage_end); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php sb_edit_link($sermon->id); ?>
    </header>

    <!-- Preacher info -->
    <?php if ($sermon->preacher_image || $sermon->preacher_description): ?>
        <aside class="preacher-info">
            <?php sb_print_preacher_image($sermon); ?>
            <?php sb_print_preacher_description($sermon); ?>
        </aside>
    <?php endif; ?>

    <!-- Description -->
    <?php if ($sermon->description): ?>
        <div class="sermon-description">
            <?php echo wp_kses_post($sermon->description); ?>
        </div>
    <?php endif; ?>

    <!-- Audio Player -->
    <?php
    $mp3_url = sb_first_mp3($sermon, true);
    if ($mp3_url):
    ?>
        <div class="sermon-player">
            <h3>Listen</h3>
            <audio controls style="width: 100%;">
                <source src="<?php echo esc_url($mp3_url); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>
    <?php endif; ?>

    <!-- All Files -->
    <div class="sermon-files">
        <h3>Downloads</h3>
        <?php
        $files = sb_get_stuff($sermon, false);
        if ($files) {
            foreach ($files as $file) {
                if ($file->stuff_type === 'file') {
                    sb_print_url($file->stuff);
                } elseif ($file->stuff_type === 'url') {
                    sb_print_url_link($file->stuff);
                } elseif ($file->stuff_type === 'code') {
                    sb_print_code($file->stuff);
                }
            }
        }
        ?>
    </div>

    <!-- Bible Text -->
    <?php if ($sermon->bible_passage): ?>
        <div class="bible-text">
            <h3>Scripture Reading</h3>
            <?php sb_add_esv_text($sermon->bible_passage, $sermon->bible_passage_end); ?>
        </div>
    <?php endif; ?>

    <!-- Tags -->
    <?php if (!empty($sermon->tags)): ?>
        <div class="sermon-tags">
            <strong>Tags:</strong>
            <?php sb_print_tags($sermon->tags); ?>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="sermon-navigation">
        <?php sb_print_prev_sermon_link($sermon); ?>
        <?php sb_print_next_sermon_link($sermon); ?>
    </nav>

    <!-- Related sermons from same day -->
    <?php sb_print_sameday_sermon_link($sermon); ?>
</article>

<?php get_footer(); ?>
```

### Widget Template

```php
<?php
/**
 * Custom Recent Sermons Widget
 */
?>
<div class="widget recent-sermons">
    <h3 class="widget-title">Recent Sermons</h3>

    <?php
    $sermons = sb_get_sermons([], 'desc', 1, 5, false);

    if ($sermons) {
        echo '<ul class="sermon-list">';
        foreach ($sermons as $sermon) {
            ?>
            <li>
                <a href="<?php echo esc_url(sb_print_sermon_link($sermon, false)); ?>">
                    <?php echo esc_html($sermon->title); ?>
                </a>
                <span class="sermon-date">
                    <?php echo sb_formatted_date($sermon); ?>
                </span>
                <span class="sermon-preacher">
                    <?php echo esc_html($sermon->preacher_name); ?>
                </span>
            </li>
            <?php
        }
        echo '</ul>';
    }
    ?>

    <p class="view-all">
        <a href="<?php echo esc_url(sb_display_url()); ?>">View All Sermons &rarr;</a>
    </p>
</div>

<!-- Tag Cloud Widget -->
<div class="widget tag-cloud">
    <h3 class="widget-title">Sermon Topics</h3>
    <?php sb_print_tag_clouds(12, 24); ?>
</div>

<!-- Popular Content Widget -->
<div class="widget popular-content">
    <h3 class="widget-title">Popular</h3>
    <?php sb_print_most_popular(); ?>
</div>
```

## Related Documentation

- [Shortcodes](./shortcodes.md) - High-level shortcode API
- [REST API](./rest-api.md) - HTTP JSON API endpoints
- [Widgets](./widgets.md) - WordPress widget API
- [Gutenberg Blocks](./gutenberg-blocks.md) - Block editor components
