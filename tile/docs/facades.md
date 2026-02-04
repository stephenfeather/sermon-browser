# Facades (Database API)

Sermon Browser provides six facade classes offering static methods for database operations. Facades provide a clean, type-safe API for CRUD operations on sermons, preachers, series, services, files, tags, and books without direct database access.

## Overview

All facade classes are located in the `\SermonBrowser\Facades\` namespace and extend `BaseFacade`. They proxy calls to repository classes which handle the actual database operations using WordPress's `$wpdb` object with prepared statements.

```php { .api }
/**
 * Base facade class
 *
 * Class: \SermonBrowser\Facades\BaseFacade
 */
abstract class BaseFacade {
    /**
     * Get the repository instance
     *
     * @return object Repository instance
     */
    protected static function getRepository(): object;
}
```

## Capabilities

### Sermon Facade

Complete CRUD operations and advanced queries for sermon records.

```php { .api }
use SermonBrowser\Facades\Sermon;

/**
 * Find sermon by ID
 *
 * @param int $id Sermon ID
 * @return object|null Sermon object or null if not found
 */
Sermon::find(int $id): ?object;

/**
 * Find all sermons with optional criteria
 *
 * @param array $criteria Filter criteria:
 *   - 'preacher' (int): Filter by preacher ID
 *   - 'series' (int): Filter by series ID
 *   - 'service' (int): Filter by service ID
 *   - 'book' (string): Filter by Bible book name
 *   - 'tag' (string): Filter by tag name
 *   - 'date' (string): Filter by date (YYYY-MM-DD)
 *   - 'enddate' (string): End date for range (YYYY-MM-DD)
 *   - 'title' (string): Search in title
 * @param int $limit Number of results (0 = unlimited)
 * @param int $offset Result offset for pagination
 * @param string $orderBy Column to sort by (default: 'sermon_date')
 * @param string $order Sort direction: 'ASC' or 'DESC' (default: 'DESC')
 * @return array Array of sermon objects
 */
Sermon::findAll(
    array $criteria = [],
    int $limit = 0,
    int $offset = 0,
    string $orderBy = 'sermon_date',
    string $order = 'DESC'
): array;

/**
 * Count sermons matching criteria
 *
 * @param array $criteria Same as findAll()
 * @return int Number of matching sermons
 */
Sermon::count(array $criteria = []): int;

/**
 * Create new sermon
 *
 * @param array $data Sermon data:
 *   - 'title' (string, required): Sermon title
 *   - 'preacher' (int): Preacher ID
 *   - 'series' (int): Series ID
 *   - 'service' (int): Service ID
 *   - 'sermon_date' (string, required): Date (YYYY-MM-DD)
 *   - 'sermon_date_time' (string): Time (HH:MM)
 *   - 'bible_passage' (string): Start passage reference
 *   - 'bible_passage_end' (string): End passage reference
 *   - 'description' (string): Sermon description
 *   - 'video_embed' (string): Video embed code
 *   - 'alternate_embed' (string): Alternate embed code
 * @return int Created sermon ID
 */
Sermon::create(array $data): int;

/**
 * Update existing sermon
 *
 * @param int $id Sermon ID
 * @param array $data Sermon data (same keys as create)
 * @return bool True on success, false on failure
 */
Sermon::update(int $id, array $data): bool;

/**
 * Delete sermon
 *
 * @param int $id Sermon ID
 * @return bool True on success, false on failure
 */
Sermon::delete(int $id): bool;

/**
 * Find sermons by preacher
 *
 * @param int $preacherId Preacher ID
 * @param int $limit Number of results (default: 10)
 * @return array Array of sermon objects
 */
Sermon::findByPreacher(int $preacherId, int $limit = 10): array;

/**
 * Find sermons by series
 *
 * @param int $seriesId Series ID
 * @param int $limit Number of results (default: 10)
 * @return array Array of sermon objects
 */
Sermon::findBySeries(int $seriesId, int $limit = 10): array;

/**
 * Find sermons by service
 *
 * @param int $serviceId Service ID
 * @param int $limit Number of results (default: 10)
 * @return array Array of sermon objects
 */
Sermon::findByService(int $serviceId, int $limit = 10): array;

/**
 * Find recent sermons
 *
 * @param int $limit Number of results (default: 10)
 * @return array Array of sermon objects
 */
Sermon::findRecent(int $limit = 10): array;

/**
 * Find sermons in date range
 *
 * @param string $startDate Start date (YYYY-MM-DD)
 * @param string $endDate End date (YYYY-MM-DD)
 * @param int $limit Number of results (0 = unlimited)
 * @return array Array of sermon objects
 */
Sermon::findByDateRange(string $startDate, string $endDate, int $limit = 0): array;

/**
 * Find sermon with all relations (files, tags, books)
 *
 * @param int $id Sermon ID
 * @return object|null Sermon object with 'files', 'tags', 'books' properties
 */
Sermon::findWithRelations(int $id): ?object;

/**
 * Find all sermons with relations
 *
 * @param array $filter Filter criteria (same as findAll)
 * @param int $limit Number of results (default: 0 = unlimited)
 * @param int $offset Result offset (default: 0)
 * @return array Array of sermon objects with relations
 */
Sermon::findAllWithRelations(array $filter = [], int $limit = 0, int $offset = 0): array;

/**
 * Search sermons by title
 *
 * @param string $search Search query
 * @param int $limit Number of results (default: 10)
 * @return array Array of sermon objects
 */
Sermon::searchByTitle(string $search, int $limit = 10): array;

/**
 * Find sermon for template rendering (includes formatted data)
 *
 * @param int $id Sermon ID
 * @return object|null Sermon object with additional formatting
 */
Sermon::findForTemplate(int $id): ?object;

/**
 * Find sermons for frontend listing (optimized query)
 *
 * @param array $filter Filter criteria
 * @param string $order Sort direction: 'ASC' or 'DESC'
 * @param int $page Page number (1-based)
 * @param int $limit Results per page
 * @param bool $hideEmpty Hide sermons without files
 * @return array Array of sermon objects
 */
Sermon::findForFrontendListing(
    array $filter = [],
    string $order = 'DESC',
    int $page = 1,
    int $limit = 10,
    bool $hideEmpty = false
): array;

/**
 * Find next sermon by date
 *
 * @param string $datetime Reference datetime
 * @param int $excludeId Sermon ID to exclude
 * @return object|null Next sermon object or null
 */
Sermon::findNextByDate(string $datetime, int $excludeId): ?object;

/**
 * Find previous sermon by date
 *
 * @param string $datetime Reference datetime
 * @param int $excludeId Sermon ID to exclude
 * @return object|null Previous sermon object or null
 */
Sermon::findPreviousByDate(string $datetime, int $excludeId): ?object;

/**
 * Find sermons on same day
 *
 * @param string $datetime Reference datetime
 * @param int $excludeId Sermon ID to exclude
 * @return array Array of sermon objects on same day
 */
Sermon::findSameDay(string $datetime, int $excludeId): array;

/**
 * Find dates for sermon IDs
 *
 * @param array $sermonIds Array of sermon IDs
 * @return array Array of dates associated with sermon IDs
 */
Sermon::findDatesForIds(array $sermonIds): array;

/**
 * Count filtered sermons
 *
 * @param array $filter Filter criteria (same as findAll)
 * @return int Number of sermons matching filter
 */
Sermon::countFiltered(array $filter = []): int;

/**
 * Find sermons for admin list with filters
 *
 * @param array $filter Filter criteria
 * @param int $limit Number of results
 * @param int $offset Result offset
 * @return array Array of sermon objects for admin display
 */
Sermon::findForAdminListFiltered(array $filter = [], int $limit = 0, int $offset = 0): array;

/**
 * Check if sermon exists
 *
 * @param int $id Sermon ID
 * @return bool True if sermon exists
 */
Sermon::exists(int $id): bool;

/**
 * Find by column value
 *
 * @param string $column Column name
 * @param mixed $value Value to match
 * @return array Array of sermon objects
 */
Sermon::findBy(string $column, mixed $value): array;

/**
 * Find one by column value
 *
 * @param string $column Column name
 * @param mixed $value Value to match
 * @return object|null First matching sermon object or null
 */
Sermon::findOneBy(string $column, mixed $value): ?object;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\Sermon;

// Get a single sermon
$sermon = Sermon::find(123);
if ($sermon) {
    echo $sermon->title;
}

// Get recent sermons
$recent = Sermon::findRecent(10);
foreach ($recent as $sermon) {
    echo $sermon->title . '<br>';
}

// Search with criteria
$sermons = Sermon::findAll([
    'preacher' => 5,
    'series' => 12,
    'date' => '2024-01-01',
    'enddate' => '2024-12-31'
], 20, 0, 'sermon_date', 'DESC');

// Get sermon with all related data
$sermon = Sermon::findWithRelations(123);
echo $sermon->title;
foreach ($sermon->files as $file) {
    echo $file->stuff;
}
foreach ($sermon->tags as $tag) {
    echo $tag->tag_name;
}

// Create new sermon
$id = Sermon::create([
    'title' => 'Grace and Truth',
    'sermon_date' => '2024-01-15',
    'preacher' => 5,
    'series' => 12,
    'bible_passage' => 'John 1:14-18',
    'description' => 'Exploring the incarnation...'
]);

// Update sermon
Sermon::update($id, [
    'title' => 'Grace and Truth (Updated)',
    'description' => 'New description...'
]);

// Delete sermon
Sermon::delete($id);
```

### Preacher Facade

CRUD operations for preacher records.

```php { .api }
use SermonBrowser\Facades\Preacher;

/**
 * Find preacher by ID
 *
 * @param int $id Preacher ID
 * @return object|null Preacher object or null
 */
Preacher::find(int $id): ?object;

/**
 * Find all preachers (unsorted)
 *
 * @return array Array of preacher objects
 */
Preacher::findAll(): array;

/**
 * Find all preachers sorted by name
 *
 * @return array Array of preacher objects sorted alphabetically
 */
Preacher::findAllSorted(): array;

/**
 * Create new preacher
 *
 * @param array $data Preacher data:
 *   - 'preacher_name' (string, required): Preacher name
 *   - 'preacher_image' (string): Image URL
 *   - 'preacher_description' (string): Biography text
 * @return int Created preacher ID
 */
Preacher::create(array $data): int;

/**
 * Update existing preacher
 *
 * @param int $id Preacher ID
 * @param array $data Preacher data (same keys as create)
 * @return bool True on success
 */
Preacher::update(int $id, array $data): bool;

/**
 * Delete preacher
 *
 * @param int $id Preacher ID
 * @return bool True on success
 */
Preacher::delete(int $id): bool;

/**
 * Find preacher by name (exact match)
 *
 * @param string $name Preacher name
 * @return object|null Preacher object or null
 */
Preacher::findByNameLike(string $name): ?object;

/**
 * Find or create preacher by name
 *
 * @param string $name Preacher name
 * @return int Preacher ID (existing or newly created)
 */
Preacher::findOrCreate(string $name): int;

/**
 * Find all preachers with sermon counts
 *
 * @return array Array of preacher objects with 'sermon_count' property
 */
Preacher::findAllWithSermonCount(): array;

/**
 * Find all preachers for filter display (formatted)
 *
 * @return array Array of preachers formatted for filter dropdowns
 */
Preacher::findAllForFilter(): array;

/**
 * Find preachers by sermon IDs with counts
 *
 * @param array $sermonIds Array of sermon IDs
 * @return array Array of preacher objects with sermon counts
 */
Preacher::findBySermonIdsWithCount(array $sermonIds): array;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\Preacher;

// Get all preachers sorted
$preachers = Preacher::findAllSorted();
foreach ($preachers as $preacher) {
    echo '<option value="' . $preacher->id . '">';
    echo $preacher->preacher_name;
    echo '</option>';
}

// Create preacher
$id = Preacher::create([
    'preacher_name' => 'John Smith',
    'preacher_image' => 'https://example.com/image.jpg',
    'preacher_description' => 'Biography text...'
]);
```

### Series Facade

CRUD operations for sermon series.

```php { .api }
use SermonBrowser\Facades\Series;

/**
 * Find series by ID
 *
 * @param int $id Series ID
 * @return object|null Series object or null
 */
Series::find(int $id): ?object;

/**
 * Find all series (unsorted)
 *
 * @return array Array of series objects
 */
Series::findAll(): array;

/**
 * Find all series sorted by name
 *
 * @return array Array of series objects sorted alphabetically
 */
Series::findAllSorted(): array;

/**
 * Create new series
 *
 * @param array $data Series data:
 *   - 'series_name' (string, required): Series name
 *   - 'series_image' (string): Image URL
 *   - 'series_description' (string): Series description
 * @return int Created series ID
 */
Series::create(array $data): int;

/**
 * Update existing series
 *
 * @param int $id Series ID
 * @param array $data Series data (same keys as create)
 * @return bool True on success
 */
Series::update(int $id, array $data): bool;

/**
 * Delete series
 *
 * @param int $id Series ID
 * @return bool True on success
 */
Series::delete(int $id): bool;

/**
 * Find series by name (exact match)
 *
 * @param string $name Series name
 * @return object|null Series object or null
 */
Series::findByNameLike(string $name): ?object;

/**
 * Find or create series by name
 *
 * @param string $name Series name
 * @return int Series ID (existing or newly created)
 */
Series::findOrCreate(string $name): int;

/**
 * Find all series with sermon counts
 *
 * @return array Array of series objects with 'sermon_count' property
 */
Series::findAllWithSermonCount(): array;

/**
 * Find all series for filter display (formatted)
 *
 * @return array Array of series formatted for filter dropdowns
 */
Series::findAllForFilter(): array;

/**
 * Find series by sermon IDs with counts
 *
 * @param array $sermonIds Array of sermon IDs
 * @return array Array of series objects with sermon counts
 */
Series::findBySermonIdsWithCount(array $sermonIds): array;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\Series;

// Get all series for dropdown
$series = Series::findAllSorted();
foreach ($series as $s) {
    echo '<option value="' . $s->id . '">' . $s->series_name . '</option>';
}

// Create series
$id = Series::create([
    'series_name' => 'Gospel of John',
    'series_description' => 'A verse-by-verse study...'
]);
```

### Service Facade

CRUD operations for church service records.

```php { .api }
use SermonBrowser\Facades\Service;

/**
 * Find service by ID
 *
 * @param int $id Service ID
 * @return object|null Service object or null
 */
Service::find(int $id): ?object;

/**
 * Find all services (unsorted)
 *
 * @return array Array of service objects
 */
Service::findAll(): array;

/**
 * Find all services sorted by name
 *
 * @return array Array of service objects sorted alphabetically
 */
Service::findAllSorted(): array;

/**
 * Create new service
 *
 * @param array $data Service data:
 *   - 'service_name' (string, required): Service name
 *   - 'service_time' (string): Service time (HH:MM)
 * @return int Created service ID
 */
Service::create(array $data): int;

/**
 * Update existing service
 *
 * @param int $id Service ID
 * @param array $data Service data (same keys as create)
 * @return bool True on success
 */
Service::update(int $id, array $data): bool;

/**
 * Delete service
 *
 * @param int $id Service ID
 * @return bool True on success
 */
Service::delete(int $id): bool;

/**
 * Update service with time shift (updates time while preserving name)
 *
 * @param int $id Service ID
 * @param string $name Service name
 * @param string $time Service time (HH:MM)
 * @return bool True on success
 */
Service::updateWithTimeShift(int $id, string $name, string $time): bool;

/**
 * Get service time
 *
 * @param int $id Service ID
 * @return string|null Service time (HH:MM) or null if not found
 */
Service::getTime(int $id): ?string;

/**
 * Find all services with sermon counts
 *
 * @return array Array of service objects with 'sermon_count' property
 */
Service::findAllWithSermonCount(): array;

/**
 * Find all services for filter display (formatted)
 *
 * @return array Array of services formatted for filter dropdowns
 */
Service::findAllForFilter(): array;

/**
 * Find services by sermon IDs with counts
 *
 * @param array $sermonIds Array of sermon IDs
 * @return array Array of service objects with sermon counts
 */
Service::findBySermonIdsWithCount(array $sermonIds): array;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\Service;

// Get all services
$services = Service::findAllSorted();
foreach ($services as $service) {
    echo $service->service_name . ' (' . $service->service_time . ')';
}

// Create service
$id = Service::create([
    'service_name' => 'Sunday Morning',
    'service_time' => '10:30'
]);
```

### File Facade

Manage sermon file attachments and download statistics.

```php { .api }
use SermonBrowser\Facades\File;

/**
 * Find file by ID
 *
 * @param int $id File ID
 * @return object|null File object or null
 */
File::find(int $id): ?object;

/**
 * Find all files for a sermon
 *
 * @param int $sermonId Sermon ID
 * @return array Array of file objects
 */
File::findBySermon(int $sermonId): array;

/**
 * Create new file record
 *
 * @param array $data File data:
 *   - 'sermon_id' (int, required): Sermon ID
 *   - 'stuff' (string, required): File path, URL, or embed code
 *   - 'stuff_type' (string, required): 'file', 'url', or 'code'
 * @return int Created file ID
 */
File::create(array $data): int;

/**
 * Delete file
 *
 * @param int $id File ID
 * @return bool True on success
 */
File::delete(int $id): bool;

/**
 * Increment download count for file by name
 *
 * @param string $filename File name
 * @return bool True on success
 */
File::incrementCountByName(string $filename): bool;

/**
 * Get total download count for all files in sermon
 *
 * @param int $sermonId Sermon ID
 * @return int Total download count
 */
File::getTotalDownloadsBySermon(int $sermonId): int;

/**
 * Find files by sermon and type
 *
 * @param int $sermonId Sermon ID
 * @param string $type File type ('file', 'url', 'code')
 * @return array Array of file objects
 */
File::findBySermonAndType(int $sermonId, string $type): array;

/**
 * Find all files by type
 *
 * @param string $type File type ('file', 'url', 'code')
 * @return array Array of file objects
 */
File::findByType(string $type): array;

/**
 * Find unlinked files (not attached to any sermon)
 *
 * @param int $limit Number of results (0 = unlimited)
 * @return array Array of unlinked file objects
 */
File::findUnlinked(int $limit = 0): array;

/**
 * Count unlinked files
 *
 * @return int Number of unlinked files
 */
File::countUnlinked(): int;

/**
 * Count linked files
 *
 * @return int Number of files attached to sermons
 */
File::countLinked(): int;

/**
 * Get total downloads across all files
 *
 * @return int Total download count
 */
File::getTotalDownloads(): int;

/**
 * Count files by type
 *
 * @param string $type File type ('file', 'url', 'code')
 * @return int Number of files of this type
 */
File::countByType(string $type): int;

/**
 * Check if file exists by name
 *
 * @param string $name File name
 * @return bool True if file exists
 */
File::existsByName(string $name): bool;

/**
 * Unlink file from sermon (remove association)
 *
 * @param int $sermonId Sermon ID
 * @return bool True on success
 */
File::unlinkFromSermon(int $sermonId): bool;

/**
 * Link file to sermon
 *
 * @param int $fileId File ID
 * @param int $sermonId Sermon ID
 * @return bool True on success
 */
File::linkToSermon(int $fileId, int $sermonId): bool;

/**
 * Delete all non-file records (URLs/codes) for a sermon
 *
 * @param int $sermonId Sermon ID
 * @return bool True on success
 */
File::deleteNonFilesBySermon(int $sermonId): bool;

/**
 * Delete files by IDs (batch delete)
 *
 * @param array $ids Array of file IDs
 * @return bool True on success
 */
File::deleteByIds(array $ids): bool;

/**
 * Delete orphaned non-file records
 *
 * @return bool True on success
 */
File::deleteOrphanedNonFiles(): bool;

/**
 * Delete empty unlinked file records
 *
 * @return bool True on success
 */
File::deleteEmptyUnlinked(): bool;

/**
 * Get all file names
 *
 * @return array Array of file name strings
 */
File::findAllFileNames(): array;

/**
 * Find files for sermon or all unlinked files
 *
 * @param int $sermonId Sermon ID (0 for unlinked only)
 * @return array Array of file objects
 */
File::findBySermonOrUnlinked(int $sermonId): array;

/**
 * Delete unlinked file by name
 *
 * @param string $name File name
 * @return bool True on success
 */
File::deleteUnlinkedByName(string $name): bool;

/**
 * Find unlinked files with title search
 *
 * @param int $limit Number of results
 * @param int $offset Result offset
 * @return array Array of unlinked file objects
 */
File::findUnlinkedWithTitle(int $limit = 0, int $offset = 0): array;

/**
 * Find linked files with title search
 *
 * @param int $limit Number of results
 * @param int $offset Result offset
 * @return array Array of linked file objects
 */
File::findLinkedWithTitle(int $limit = 0, int $offset = 0): array;

/**
 * Search files by name
 *
 * @param string $search Search query
 * @param int $limit Number of results
 * @param int $offset Result offset
 * @return array Array of file objects matching search
 */
File::searchByName(string $search, int $limit = 0, int $offset = 0): array;

/**
 * Count files matching search
 *
 * @param string $search Search query
 * @return int Number of matching files
 */
File::countBySearch(string $search): int;

/**
 * Get file duration (for MP3/video files)
 *
 * @param string $name File name
 * @return string|null Duration in HH:MM:SS format or null
 */
File::getFileDuration(string $name): ?string;

/**
 * Set file duration
 *
 * @param string $name File name
 * @param string $duration Duration in HH:MM:SS format
 * @return bool True on success
 */
File::setFileDuration(string $name, string $duration): bool;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\File;

// Get all files for a sermon
$files = File::findBySermon(123);
foreach ($files as $file) {
    if ($file->stuff_type === 'file') {
        echo '<a href="/download/' . $file->stuff . '">';
        echo basename($file->stuff);
        echo ' (' . $file->download_count . ' downloads)</a>';
    }
}

// Add file to sermon
$id = File::create([
    'sermon_id' => 123,
    'stuff' => 'sermons/sermon-123.mp3',
    'stuff_type' => 'file'
]);

// Track download
File::incrementCountByName('sermon-123.mp3');

// Get total downloads
$total = File::getTotalDownloadsBySermon(123);
echo "Total downloads: $total";
```

### Tag Facade

Access sermon tags.

```php { .api }
use SermonBrowser\Facades\Tag;

/**
 * Find tag by ID
 *
 * @param int $id Tag ID
 * @return object|null Tag object or null
 */
Tag::find(int $id): ?object;

/**
 * Find all tags
 *
 * @return array Array of tag objects
 */
Tag::findAll(): array;

/**
 * Find all tags for a sermon
 *
 * @param int $sermonId Sermon ID
 * @return array Array of tag objects
 */
Tag::findBySermon(int $sermonId): array;

/**
 * Create new tag
 *
 * @param array $data Tag data:
 *   - 'tag_name' (string, required): Tag name
 * @return int Created tag ID
 */
Tag::create(array $data): int;

/**
 * Delete tag
 *
 * @param int $id Tag ID
 * @return bool True on success
 */
Tag::delete(int $id): bool;

/**
 * Find tag by name
 *
 * @param string $name Tag name
 * @return object|null Tag object or null
 */
Tag::findByName(string $name): ?object;

/**
 * Find or create tag by name
 *
 * @param string $name Tag name
 * @return int Tag ID (existing or newly created)
 */
Tag::findOrCreate(string $name): int;

/**
 * Find all tags sorted by name
 *
 * @return array Array of tag objects sorted alphabetically
 */
Tag::findAllSorted(): array;

/**
 * Attach tag to sermon (create relationship)
 *
 * @param int $sermonId Sermon ID
 * @param int $tagId Tag ID
 * @return bool True on success
 */
Tag::attachToSermon(int $sermonId, int $tagId): bool;

/**
 * Detach tag from sermon (remove relationship)
 *
 * @param int $sermonId Sermon ID
 * @param int $tagId Tag ID
 * @return bool True on success
 */
Tag::detachFromSermon(int $sermonId, int $tagId): bool;

/**
 * Detach all tags from sermon
 *
 * @param int $sermonId Sermon ID
 * @return bool True on success
 */
Tag::detachAllFromSermon(int $sermonId): bool;

/**
 * Find all tags with sermon counts
 *
 * @param int $limit Number of results (0 = unlimited)
 * @return array Array of tag objects with 'sermon_count' property
 */
Tag::findAllWithSermonCount(int $limit = 0): array;

/**
 * Delete unused tags (tags with no sermons)
 *
 * @return int Number of tags deleted
 */
Tag::deleteUnused(): int;

/**
 * Count non-empty tags (tags with at least one sermon)
 *
 * @return int Number of tags with sermons
 */
Tag::countNonEmpty(): int;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\Tag;

// Get tags for a sermon
$tags = Tag::findBySermon(123);
foreach ($tags as $tag) {
    echo '<span class="tag">' . $tag->tag_name . '</span> ';
}

// Get all tags
$allTags = Tag::findAll();
```

### Book Facade

Access Bible book names.

```php { .api }
use SermonBrowser\Facades\Book;

/**
 * Find all Bible book names
 *
 * @return array Array of book name strings
 */
Book::findAllNames(): array;

/**
 * Truncate books table (delete all records)
 *
 * @return bool True on success
 */
Book::truncate(): bool;

/**
 * Insert a book record
 *
 * @param string $name Book name
 * @return int Inserted book ID
 */
Book::insertBook(string $name): int;

/**
 * Update book name in all sermons
 *
 * @param string $newName New book name
 * @param string $oldName Old book name to replace
 * @return bool True on success
 */
Book::updateBookNameInSermons(string $newName, string $oldName): bool;

/**
 * Delete all book references for a sermon
 *
 * @param int $sermonId Sermon ID
 * @return bool True on success
 */
Book::deleteBySermonId(int $sermonId): bool;

/**
 * Insert passage reference
 *
 * @param string $book Book name
 * @param string $chapter Chapter number
 * @param string $verse Verse number
 * @param int $order Display order
 * @param string $type Reference type ('start' or 'end')
 * @param int $sermonId Sermon ID
 * @return int Inserted reference ID
 */
Book::insertPassageRef(string $book, string $chapter, string $verse, int $order, string $type, int $sermonId): int;

/**
 * Find all book references for a sermon
 *
 * @param int $sermonId Sermon ID
 * @return array Array of book reference objects
 */
Book::findBySermonId(int $sermonId): array;

/**
 * Reset books for locale (update book names to localized versions)
 *
 * @param array $books Localized book names
 * @param array $engBooks English book names
 * @return void
 */
Book::resetBooksForLocale(array $books, array $engBooks): void;

/**
 * Get all sermons with verse data
 *
 * @return array Array of sermon objects with verse data
 */
Book::getSermonsWithVerseData(): array;

/**
 * Update sermon verse data
 *
 * @param int $sermonId Sermon ID
 * @param string $start Start verse reference
 * @param string $end End verse reference
 * @return bool True on success
 */
Book::updateSermonVerseData(int $sermonId, string $start, string $end): bool;

/**
 * Find all books with sermon counts
 *
 * @return array Array of book names with 'sermon_count' property
 */
Book::findAllWithSermonCount(): array;

/**
 * Find books by sermon IDs with counts
 *
 * @param array $sermonIds Array of sermon IDs
 * @return array Array of book names with sermon counts
 */
Book::findBySermonIdsWithCount(array $sermonIds): array;
```

**Usage Examples:**

```php
use SermonBrowser\Facades\Book;

// Get all Bible books for dropdown
$books = Book::findAllNames();
foreach ($books as $book) {
    echo '<option value="' . $book . '">' . $book . '</option>';
}
```

## Error Handling

All facade methods return `null` or `false` on failure. Check return values:

```php
$sermon = Sermon::find(999);
if ($sermon === null) {
    // Sermon not found
    wp_die('Sermon not found');
}

$success = Sermon::delete($id);
if (!$success) {
    // Deletion failed
    error_log('Failed to delete sermon ' . $id);
}
```

## Transaction Support

For operations requiring multiple database changes, facades do not provide built-in transaction support. Use WordPress's `$wpdb` directly:

```php
global $wpdb;

$wpdb->query('START TRANSACTION');

try {
    $sermonId = Sermon::create($sermonData);
    File::create(['sermon_id' => $sermonId, 'stuff' => 'file.mp3', 'stuff_type' => 'file']);
    $wpdb->query('COMMIT');
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    error_log($e->getMessage());
}
```

## Repository Layer

Facades delegate to repository classes in `\SermonBrowser\Repositories\*`. Repositories handle:
- SQL query construction with `$wpdb->prepare()`
- Result mapping to objects
- Error handling
- Input validation

Direct repository access is also available if needed:

```php
use SermonBrowser\Repositories\SermonRepository;

$repo = new SermonRepository();
$sermons = $repo->findAll(['preacher' => 5], 10);
```
