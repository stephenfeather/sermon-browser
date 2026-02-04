# Shortcodes

Sermon Browser provides two shortcodes for displaying sermons in WordPress posts and pages: `[sermons]` for listing multiple sermons and `[sermon]` for displaying a single sermon. Both shortcodes support extensive filtering and customization options.

## Capabilities

### Sermons List Shortcode

Display a filterable list of sermons with optional dropdown or one-click filters.

```php { .api }
/**
 * Display multiple sermons with filtering
 *
 * Shortcode: [sermons]
 * Handler function: sb_shortcode()
 */
[sermons
    filter="dropdown|oneclick|none"  // Filter UI type (default: "dropdown")
    filterhide="true|false"           // Hide filter UI (default: false)
    preacher="ID"                     // Filter by preacher ID
    series="ID"                       // Filter by series ID
    book="book_name"                  // Filter by Bible book name
    service="ID"                      // Filter by service ID
    date="YYYY-MM-DD"                 // Filter by date
    enddate="YYYY-MM-DD"              // End date for date range
    tag="tag_slug"                    // Filter by tag slug
    title="search_text"               // Search by title text
    limit="number"                    // Number of sermons to display
    dir="asc|desc"                    // Sort direction (default: "desc")
]
```

**Examples:**

```php
// Display 10 most recent sermons with dropdown filters
[sermons limit="10" filter="dropdown"]

// Display sermons by specific preacher
[sermons preacher="5" limit="20"]

// Display sermons in a series without filters
[sermons series="12" filter="none"]

// Display sermons from a date range
[sermons date="2024-01-01" enddate="2024-12-31"]

// Display sermons by Bible book
[sermons book="John" limit="15"]

// Display sermons with one-click filter buttons
[sermons filter="oneclick" limit="10"]

// Search sermons by title
[sermons title="grace" limit="10"]

// Combined filters
[sermons preacher="5" series="12" service="1" limit="20" dir="asc"]
```

### Single Sermon Shortcode

Display a single sermon's details including title, preacher, date, Bible passage, description, attached files, and embedded media.

```php { .api }
/**
 * Display single sermon details
 *
 * Shortcode: [sermon]
 * Handler function: sb_shortcode()
 */
[sermon
    id="ID|latest"  // Sermon ID or "latest" for most recent sermon
]
```

**Examples:**

```php
// Display specific sermon
[sermon id="123"]

// Display most recent sermon
[sermon id="latest"]
```

## Shortcode Handler

Both shortcodes are processed by the `sb_shortcode()` function defined in the main plugin file:

```php { .api }
/**
 * Process [sermons] and [sermon] shortcodes
 *
 * @param array $atts Shortcode attributes
 * @param string|null $content Shortcode content (not used)
 * @param string $tag Shortcode tag name
 * @return string HTML output
 */
function sb_shortcode($atts, $content = null, $tag): string;
```

## Filter Types

### Dropdown Filters

The `filter="dropdown"` option displays dropdown select menus for filtering by:
- Preacher
- Series
- Bible book
- Service
- Date (year/month)
- Tag

### One-Click Filters

The `filter="oneclick"` option displays clickable buttons or links for quick filtering without dropdowns.

### No Filters

The `filter="none"` option displays only the sermon list without any filter UI.

## Template Integration

Shortcodes use the plugin's template system to render output. The templates can be customized via the WordPress admin panel under Sermons > Options > Templates.

**Template Types:**
- **Search Template**: Used for `[sermons]` multi-sermon lists
- **Single Template**: Used for `[sermon]` single sermon display

## Usage in Templates

Shortcodes can be used programmatically in theme templates:

```php
// In template files
echo do_shortcode('[sermons limit="10" filter="dropdown"]');

// With dynamic attributes
$preacher_id = 5;
echo do_shortcode('[sermons preacher="' . $preacher_id . '" limit="20"]');

// In widgets
echo do_shortcode('[sermon id="latest"]');
```

## Pagination

The `[sermons]` shortcode automatically includes pagination when the number of sermons exceeds the `limit` parameter. Pagination links are generated using the template system's `[pagination]` tag.

## Caching

Shortcode output is not cached by default, but the underlying template rendering uses WordPress transients for caching (60-minute TTL). Cache is automatically cleared when sermons are added, updated, or deleted.

## Attributes Reference

| Attribute   | Type   | Default     | Description                                    |
|-------------|--------|-------------|------------------------------------------------|
| filter      | string | "dropdown"  | Filter UI type: "dropdown", "oneclick", "none" |
| filterhide  | bool   | false       | Hide filter UI completely                      |
| id          | mixed  | null        | Sermon ID or "latest"                          |
| preacher    | int    | null        | Filter by preacher ID                          |
| series      | int    | null        | Filter by series ID                            |
| book        | string | null        | Filter by Bible book name                      |
| service     | int    | null        | Filter by service ID                           |
| date        | string | null        | Filter by date (YYYY-MM-DD)                    |
| enddate     | string | null        | End date for range (YYYY-MM-DD)                |
| tag         | string | null        | Filter by tag slug                             |
| title       | string | null        | Search by title text                           |
| limit       | int    | 10          | Number of sermons to display                   |
| dir         | string | "desc"      | Sort direction: "asc" or "desc"                |

## URL Parameters

When filters are used, they add query parameters to the URL allowing for shareable filtered views:

```
?preacher=5&series=12&sb_action=display
```

These URL parameters work with both shortcodes and direct page access.
