# Gutenberg Blocks

Sermon Browser provides twelve Gutenberg blocks and five pre-built block patterns for Full Site Editing (FSE) support. All blocks use the `sermon-browser` namespace and integrate with the WordPress block editor.

## Block Registration

All blocks are registered via the `BlockRegistry` class:

```php { .api }
/**
 * Block registry and initialization
 *
 * Class: \SermonBrowser\Blocks\BlockRegistry
 */
class BlockRegistry {
    /**
     * Register all Sermon Browser blocks
     * Called on 'init' hook
     */
    public static function registerAll(): void;
}
```

## Capabilities

### Sermon List Block

Display a filterable, paginated list of sermons with AJAX-powered dynamic filtering.

```php { .api }
/**
 * Block: sermon-browser/sermon-list
 * Class: \SermonBrowser\Blocks\SermonListBlock
 *
 * Block attributes:
 * - limit (int): Number of sermons per page (default: 10)
 * - filter (string): Filter type - "dropdown", "oneclick", "none" (default: "dropdown")
 * - preacher (int): Filter by preacher ID (default: 0 = all)
 * - series (int): Filter by series ID (default: 0 = all)
 * - service (int): Filter by service ID (default: 0 = all)
 * - showPagination (bool): Show pagination controls (default: true)
 * - orderDirection (string): Sort order - "asc", "desc" (default: "desc")
 */
```

### Single Sermon Block

Display details of a single sermon including title, preacher, date, Bible passage, description, and attached files.

```php { .api }
/**
 * Block: sermon-browser/single-sermon
 * Class: \SermonBrowser\Blocks\SingleSermonBlock
 *
 * Block attributes:
 * - sermonId (int): Sermon ID to display (default: 0 = latest)
 * - showPreacher (bool): Display preacher name (default: true)
 * - showDate (bool): Display sermon date (default: true)
 * - showBiblePassage (bool): Display Bible passage (default: true)
 * - showDescription (bool): Display description (default: true)
 * - showFiles (bool): Display attached files (default: true)
 */
```

### Tag Cloud Block

Display a tag cloud of sermon topics with font sizes based on usage frequency.

```php { .api }
/**
 * Block: sermon-browser/tag-cloud
 * Class: \SermonBrowser\Blocks\TagCloudBlock
 *
 * Block attributes:
 * - minFontSize (int): Minimum font size in pixels (default: 12)
 * - maxFontSize (int): Maximum font size in pixels (default: 24)
 */
```

### Preacher List Block

Display a list of all preachers with optional images and biographies.

```php { .api }
/**
 * Block: sermon-browser/preacher-list
 * Class: \SermonBrowser\Blocks\PreacherListBlock
 *
 * Block attributes:
 * - showImages (bool): Display preacher images (default: true)
 * - showBios (bool): Display preacher biographies (default: false)
 * - layout (string): Display layout - "grid", "list" (default: "grid")
 */
```

### Series Grid Block

Display sermon series in a grid layout with series images and descriptions.

```php { .api }
/**
 * Block: sermon-browser/series-grid
 * Class: \SermonBrowser\Blocks\SeriesGridBlock
 *
 * Block attributes:
 * - columns (int): Number of columns (default: 3)
 * - showImages (bool): Display series images (default: true)
 * - showDescriptions (bool): Display series descriptions (default: true)
 * - showSermonCount (bool): Display sermon count per series (default: true)
 */
```

### Sermon Player Block

Display an audio/video player for sermon media files.

```php { .api }
/**
 * Block: sermon-browser/sermon-player
 * Class: \SermonBrowser\Blocks\SermonPlayerBlock
 *
 * Block attributes:
 * - sermonId (int): Sermon ID to play (default: 0 = latest)
 * - autoplay (bool): Autoplay on load (default: false)
 * - showPlaylist (bool): Show playlist of all files (default: true)
 */
```

### Recent Sermons Block

Display a list of the most recent sermons.

```php { .api }
/**
 * Block: sermon-browser/recent-sermons
 * Class: \SermonBrowser\Blocks\RecentSermonsBlock
 *
 * Block attributes:
 * - limit (int): Number of sermons to display (default: 5)
 * - showPreacher (bool): Display preacher name (default: true)
 * - showDate (bool): Display sermon date (default: true)
 * - showExcerpt (bool): Display excerpt (default: false)
 */
```

### Popular Sermons Block

Display most popular sermons based on download counts.

```php { .api }
/**
 * Block: sermon-browser/popular-sermons
 * Class: \SermonBrowser\Blocks\PopularSermonsBlock
 *
 * Block attributes:
 * - limit (int): Number of sermons to display (default: 5)
 * - showDownloadCount (bool): Display download count (default: true)
 * - timeRange (string): Time range - "all", "week", "month", "year" (default: "all")
 */
```

### Sermon Grid Block

Display sermons in a grid layout with featured images.

```php { .api }
/**
 * Block: sermon-browser/sermon-grid
 * Class: \SermonBrowser\Blocks\SermonGridBlock
 *
 * Block attributes:
 * - columns (int): Number of columns (default: 3)
 * - limit (int): Number of sermons (default: 12)
 * - showPreacher (bool): Display preacher (default: true)
 * - showDate (bool): Display date (default: true)
 * - showExcerpt (bool): Display excerpt (default: true)
 */
```

### Profile Block

Display a preacher profile with image, biography, and recent sermons.

```php { .api }
/**
 * Block: sermon-browser/profile-block
 * Class: \SermonBrowser\Blocks\ProfileBlock
 *
 * Block attributes:
 * - preacherId (int): Preacher ID to display
 * - showImage (bool): Display preacher image (default: true)
 * - showBio (bool): Display biography (default: true)
 * - showRecentSermons (bool): Display recent sermons (default: true)
 * - recentLimit (int): Number of recent sermons (default: 5)
 */
```

### Sermon Media Block

Display media attachments (audio/video/documents) for a sermon.

```php { .api }
/**
 * Block: sermon-browser/sermon-media
 * Class: \SermonBrowser\Blocks\SermonMediaBlock
 *
 * Block attributes:
 * - sermonId (int): Sermon ID (default: 0 = latest)
 * - layout (string): Display layout - "list", "grid" (default: "list")
 * - showIcons (bool): Display file type icons (default: true)
 * - showDownloadCounts (bool): Display download counts (default: false)
 */
```

### Sermon Filters Block

Display filter controls for sermon lists (used with Sermon List block).

```php { .api }
/**
 * Block: sermon-browser/sermon-filters
 * Class: \SermonBrowser\Blocks\SermonFiltersBlock
 *
 * Block attributes:
 * - filterType (string): Filter UI type - "dropdown", "oneclick" (default: "dropdown")
 * - showPreacherFilter (bool): Show preacher filter (default: true)
 * - showSeriesFilter (bool): Show series filter (default: true)
 * - showServiceFilter (bool): Show service filter (default: true)
 * - showBookFilter (bool): Show Bible book filter (default: true)
 * - showDateFilter (bool): Show date filter (default: true)
 * - showTagFilter (bool): Show tag filter (default: true)
 */
```

## Block Patterns

Pre-built block patterns combining multiple blocks for common layouts:

### Featured Sermon Hero

Hero section with a featured sermon display.

```php { .api }
/**
 * Pattern: sermon-browser/featured-sermon-hero
 * File: src/Blocks/patterns/featured-sermon-hero.php
 *
 * Contains:
 * - Single Sermon Block (latest sermon)
 * - Sermon Player Block
 * - Large typography and spacing
 */
```

### Sermon Archive Page

Full-page sermon archive layout with filters and pagination.

```php { .api }
/**
 * Pattern: sermon-browser/sermon-archive-page
 * File: src/Blocks/patterns/sermon-archive-page.php
 *
 * Contains:
 * - Page heading
 * - Sermon Filters Block
 * - Sermon List Block with pagination
 * - Sidebar with Tag Cloud and Popular Sermons
 */
```

### Preacher Spotlight

Highlighted preacher profile section.

```php { .api }
/**
 * Pattern: sermon-browser/preacher-spotlight
 * File: src/Blocks/patterns/preacher-spotlight.php
 *
 * Contains:
 * - Profile Block
 * - Recent sermons by preacher
 * - Call-to-action for more sermons
 */
```

### Popular This Week

Display most popular sermons from the past week.

```php { .api }
/**
 * Pattern: sermon-browser/popular-this-week
 * File: src/Blocks/patterns/popular-this-week.php
 *
 * Contains:
 * - Section heading
 * - Popular Sermons Block (timeRange: "week")
 * - Styled card layout
 */
```

### Tag Cloud Sidebar

Sidebar section with sermon topics tag cloud.

```php { .api }
/**
 * Pattern: sermon-browser/tag-cloud-sidebar
 * File: src/Blocks/patterns/tag-cloud-sidebar.php
 *
 * Contains:
 * - Sidebar heading
 * - Tag Cloud Block
 * - Optional "Browse all topics" link
 */
```

## Full Site Editing (FSE) Support

FSE support provided through template parts and block templates:

```php { .api }
/**
 * FSE support class
 *
 * Class: \SermonBrowser\Blocks\FSESupport
 *
 * Template parts:
 * - sermon-archive.html: Archive page template
 * - single-sermon.html: Single sermon template
 */
class FSESupport {
    /**
     * Register template parts and block templates
     * Called on 'init' hook
     */
    public static function register(): void;
}
```

## Block Editor Integration

Blocks are registered with WordPress using `register_block_type()`:

```php { .api }
/**
 * Register a block with WordPress
 *
 * @param string $name Block name (without namespace)
 * @param array $args Block registration arguments
 */
register_block_type('sermon-browser/' . $name, $args);
```

## Block Assets

Each block includes:
- **JavaScript**: Block editor functionality (in `assets/js/blocks/`)
- **CSS**: Block styling (in `assets/css/blocks/`)
- **PHP**: Server-side rendering (in `src/Blocks/`)

Assets are automatically enqueued when blocks are used on a page.

## Server-Side Rendering

All blocks use server-side rendering for dynamic content:

```php { .api }
/**
 * Render block content on the server
 *
 * Each block class implements:
 */
public static function render($attributes, $content): string;
```

## Block Deprecation

Blocks follow WordPress block deprecation patterns to maintain backward compatibility when block structure changes. Deprecated versions are stored in block metadata.

## Block Variations

Some blocks support variations for common use cases. For example, the Sermon List block has variations:
- **Default**: Standard list with all filters
- **By Preacher**: Pre-filtered by preacher
- **By Series**: Pre-filtered by series
- **Recent Only**: Recent sermons without filters
