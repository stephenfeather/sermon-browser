# REST API

Sermon Browser provides a comprehensive RESTful API for CRUD operations on sermons, preachers, series, services, files, tags, and search. All endpoints are namespaced under `sermon-browser/v1` and support standard HTTP methods.

## API Configuration

```php { .api }
// REST API namespace
const REST_NAMESPACE = 'sermon-browser/v1';

// Base URL
// https://example.com/wp-json/sermon-browser/v1/

// Authentication: WordPress authentication (cookies, application passwords, or OAuth)
// Rate limiting: Applied per IP address
//   - Anonymous: 60 requests/minute (20 for search)
//   - Authenticated: 120 requests/minute (60 for search)
```

## Rate Limiting

```php { .api }
/**
 * Rate limiter class
 *
 * Class: \SermonBrowser\REST\RateLimiter
 */
class RateLimiter {
    const RATE_LIMIT_ANON = 60;           // Anonymous requests per minute
    const RATE_LIMIT_AUTH = 120;          // Authenticated requests per minute
    const RATE_LIMIT_SEARCH_ANON = 20;    // Anonymous search requests per minute
    const RATE_LIMIT_SEARCH_AUTH = 60;    // Authenticated search requests per minute

    /**
     * Check if request should be rate limited
     *
     * @param string $identifier User IP or user ID
     * @param string $type Request type ('default' or 'search')
     * @return bool True if rate limited
     */
    public static function isRateLimited($identifier, $type = 'default'): bool;
}
```

## Capabilities

### Sermons Controller

Full CRUD operations for sermon management with filtering, pagination, and rendering.

```php { .api }
/**
 * Sermons REST controller
 *
 * Class: \SermonBrowser\REST\SermonsController
 * Base route: /sermons
 */

/**
 * GET /wp-json/sermon-browser/v1/sermons
 *
 * List sermons with optional filtering and pagination
 *
 * Query parameters:
 * - preacher (int): Filter by preacher ID
 * - series (int): Filter by series ID
 * - service (int): Filter by service ID
 * - search (string): Search in sermon titles
 * - page (int): Page number (default: 1)
 * - per_page (int): Results per page (default: 10, max: 100)
 *
 * Response: Array of sermon objects
 * {
 *   "id": 123,
 *   "title": "Grace and Truth",
 *   "preacher": 5,
 *   "preacher_name": "John Smith",
 *   "series": 12,
 *   "series_name": "Gospel of John",
 *   "service": 1,
 *   "service_name": "Sunday Morning",
 *   "sermon_date": "2024-01-15",
 *   "sermon_date_time": "10:30",
 *   "bible_passage": "John 1:14-18",
 *   "bible_passage_end": "",
 *   "description": "Exploring the incarnation...",
 *   "video_embed": "",
 *   "alternate_embed": ""
 * }
 */
GET /sermons

/**
 * POST /wp-json/sermon-browser/v1/sermons
 *
 * Create a new sermon
 *
 * Required capability: edit_posts
 *
 * Request body:
 * {
 *   "title": "string (required)",
 *   "preacher": "int (optional)",
 *   "series": "int (optional)",
 *   "service": "int (optional)",
 *   "sermon_date": "YYYY-MM-DD (required)",
 *   "sermon_date_time": "HH:MM (optional)",
 *   "bible_passage": "string (optional)",
 *   "bible_passage_end": "string (optional)",
 *   "description": "string (optional)",
 *   "video_embed": "string (optional)",
 *   "alternate_embed": "string (optional)"
 * }
 *
 * Response: Created sermon object with ID
 */
POST /sermons

/**
 * GET /wp-json/sermon-browser/v1/sermons/{id}
 *
 * Get a single sermon by ID
 *
 * Path parameters:
 * - id (int): Sermon ID
 *
 * Response: Single sermon object with all related data (files, tags, books)
 */
GET /sermons/{id}

/**
 * PUT /wp-json/sermon-browser/v1/sermons/{id}
 * PATCH /wp-json/sermon-browser/v1/sermons/{id}
 *
 * Update an existing sermon
 *
 * Required capability: edit_posts
 *
 * Path parameters:
 * - id (int): Sermon ID
 *
 * Request body: Partial or full sermon object (same as POST)
 *
 * Response: Updated sermon object
 */
PUT /sermons/{id}
PATCH /sermons/{id}

/**
 * DELETE /wp-json/sermon-browser/v1/sermons/{id}
 *
 * Delete a sermon
 *
 * Required capability: edit_posts
 *
 * Path parameters:
 * - id (int): Sermon ID
 *
 * Response: Success message
 * {
 *   "success": true,
 *   "message": "Sermon deleted successfully"
 * }
 */
DELETE /sermons/{id}

/**
 * GET /wp-json/sermon-browser/v1/sermons/render
 *
 * Render sermon list as HTML (for AJAX dynamic filtering)
 *
 * Query parameters: Same as GET /sermons
 *
 * Response: HTML string
 * {
 *   "html": "<div class='sermon-list'>...</div>"
 * }
 */
GET /sermons/render
```

### Preachers Controller

Manage preacher records with CRUD operations.

```php { .api }
/**
 * Preachers REST controller
 *
 * Class: \SermonBrowser\REST\PreachersController
 * Base route: /preachers
 */

/**
 * GET /wp-json/sermon-browser/v1/preachers
 *
 * List all preachers
 *
 * Response: Array of preacher objects
 * {
 *   "id": 5,
 *   "preacher_name": "John Smith",
 *   "preacher_image": "https://example.com/image.jpg",
 *   "preacher_description": "Biography text..."
 * }
 */
GET /preachers

/**
 * POST /wp-json/sermon-browser/v1/preachers
 *
 * Create a new preacher
 *
 * Required capability: manage_categories
 *
 * Request body:
 * {
 *   "preacher_name": "string (required)",
 *   "preacher_image": "string (optional, URL)",
 *   "preacher_description": "string (optional)"
 * }
 *
 * Response: Created preacher object with ID
 */
POST /preachers

/**
 * GET /wp-json/sermon-browser/v1/preachers/{id}
 *
 * Get a single preacher by ID
 *
 * Path parameters:
 * - id (int): Preacher ID
 *
 * Response: Single preacher object
 */
GET /preachers/{id}

/**
 * PUT /wp-json/sermon-browser/v1/preachers/{id}
 * PATCH /wp-json/sermon-browser/v1/preachers/{id}
 *
 * Update an existing preacher
 *
 * Required capability: manage_categories
 *
 * Request body: Partial or full preacher object
 *
 * Response: Updated preacher object
 */
PUT /preachers/{id}
PATCH /preachers/{id}

/**
 * DELETE /wp-json/sermon-browser/v1/preachers/{id}
 *
 * Delete a preacher
 *
 * Required capability: manage_categories
 *
 * Response: Success message
 */
DELETE /preachers/{id}
```

### Series Controller

Manage sermon series with CRUD operations and related sermons.

```php { .api }
/**
 * Series REST controller
 *
 * Class: \SermonBrowser\REST\SeriesController
 * Base route: /series
 */

/**
 * GET /wp-json/sermon-browser/v1/series
 *
 * List all series
 *
 * Response: Array of series objects
 * {
 *   "id": 12,
 *   "series_name": "Gospel of John",
 *   "series_image": "https://example.com/image.jpg",
 *   "series_description": "Series description..."
 * }
 */
GET /series

/**
 * POST /wp-json/sermon-browser/v1/series
 *
 * Create a new series
 *
 * Required capability: manage_categories
 *
 * Request body:
 * {
 *   "series_name": "string (required)",
 *   "series_image": "string (optional, URL)",
 *   "series_description": "string (optional)"
 * }
 *
 * Response: Created series object with ID
 */
POST /series

/**
 * GET /wp-json/sermon-browser/v1/series/{id}
 *
 * Get a single series by ID
 *
 * Response: Single series object
 */
GET /series/{id}

/**
 * PUT /wp-json/sermon-browser/v1/series/{id}
 * PATCH /wp-json/sermon-browser/v1/series/{id}
 *
 * Update an existing series
 *
 * Required capability: manage_categories
 *
 * Response: Updated series object
 */
PUT /series/{id}
PATCH /series/{id}

/**
 * DELETE /wp-json/sermon-browser/v1/series/{id}
 *
 * Delete a series
 *
 * Required capability: manage_categories
 *
 * Response: Success message
 */
DELETE /series/{id}

/**
 * GET /wp-json/sermon-browser/v1/series/{id}/sermons
 *
 * Get all sermons in a series
 *
 * Path parameters:
 * - id (int): Series ID
 *
 * Query parameters:
 * - page (int): Page number (default: 1)
 * - per_page (int): Results per page (default: 10, max: 100)
 *
 * Response: Array of sermon objects
 */
GET /series/{id}/sermons
```

### Services Controller

Manage church service records with CRUD operations.

```php { .api }
/**
 * Services REST controller
 *
 * Class: \SermonBrowser\REST\ServicesController
 * Base route: /services
 */

/**
 * GET /wp-json/sermon-browser/v1/services
 *
 * List all services
 *
 * Response: Array of service objects
 * {
 *   "id": 1,
 *   "service_name": "Sunday Morning",
 *   "service_time": "10:30"
 * }
 */
GET /services

/**
 * POST /wp-json/sermon-browser/v1/services
 *
 * Create a new service
 *
 * Required capability: manage_categories
 *
 * Request body:
 * {
 *   "service_name": "string (required)",
 *   "service_time": "HH:MM (optional)"
 * }
 *
 * Response: Created service object with ID
 */
POST /services

/**
 * GET /wp-json/sermon-browser/v1/services/{id}
 *
 * Get a single service by ID
 *
 * Response: Single service object
 */
GET /services/{id}

/**
 * PUT /wp-json/sermon-browser/v1/services/{id}
 * PATCH /wp-json/sermon-browser/v1/services/{id}
 *
 * Update an existing service
 *
 * Required capability: manage_categories
 *
 * Response: Updated service object
 */
PUT /services/{id}
PATCH /services/{id}

/**
 * DELETE /wp-json/sermon-browser/v1/services/{id}
 *
 * Delete a service
 *
 * Required capability: manage_categories
 *
 * Response: Success message
 */
DELETE /services/{id}
```

### Files Controller

Manage sermon file attachments (audio, video, documents).

```php { .api }
/**
 * Files REST controller
 *
 * Class: \SermonBrowser\REST\FilesController
 * Base route: /files
 */

/**
 * GET /wp-json/sermon-browser/v1/files
 *
 * List all files
 *
 * Query parameters:
 * - sermon_id (int): Filter by sermon ID
 *
 * Response: Array of file objects
 * {
 *   "id": 456,
 *   "sermon_id": 123,
 *   "stuff": "sermon-123.mp3",
 *   "stuff_type": "file",
 *   "download_count": 42
 * }
 */
GET /files

/**
 * POST /wp-json/sermon-browser/v1/files
 *
 * Upload a file attachment
 *
 * Required capability: upload_files
 *
 * Request body (multipart/form-data):
 * {
 *   "sermon_id": "int (required)",
 *   "file": "file upload (required)",
 *   "stuff_type": "file|url|code (default: file)"
 * }
 *
 * Response: Created file object with ID
 */
POST /files

/**
 * GET /wp-json/sermon-browser/v1/files/{id}
 *
 * Get a single file by ID
 *
 * Response: Single file object
 */
GET /files/{id}

/**
 * DELETE /wp-json/sermon-browser/v1/files/{id}
 *
 * Delete a file
 *
 * Required capability: delete_posts
 *
 * Response: Success message
 */
DELETE /files/{id}
```

### Tags Controller

Retrieve sermon tags (read-only).

```php { .api }
/**
 * Tags REST controller
 *
 * Class: \SermonBrowser\REST\TagsController
 * Base route: /tags
 */

/**
 * GET /wp-json/sermon-browser/v1/tags
 *
 * List all tags
 *
 * Response: Array of tag objects
 * {
 *   "id": 78,
 *   "tag_name": "Grace"
 * }
 */
GET /tags

/**
 * GET /wp-json/sermon-browser/v1/tags/{id}
 *
 * Get a single tag by ID
 *
 * Response: Single tag object
 */
GET /tags/{id}
```

### Search Controller

Combined search across sermons with text and filter criteria.

```php { .api }
/**
 * Search REST controller
 *
 * Class: \SermonBrowser\REST\SearchController
 * Base route: /search
 */

/**
 * GET /wp-json/sermon-browser/v1/search
 *
 * Search sermons with combined text and filters
 *
 * Query parameters:
 * - q (string): Search query (searches title and description)
 * - preacher (int): Filter by preacher ID
 * - series (int): Filter by series ID
 * - service (int): Filter by service ID
 * - page (int): Page number (default: 1)
 * - per_page (int): Results per page (default: 10, max: 100)
 *
 * Response: Array of sermon objects matching search criteria
 */
GET /search
```

## Authentication

The REST API uses WordPress's built-in authentication:

```php { .api }
/**
 * Authentication methods:
 *
 * 1. Cookie Authentication (for logged-in users)
 *    - Requires nonce header: X-WP-Nonce
 *
 * 2. Application Passwords (WordPress 5.6+)
 *    - Use Basic Auth with application password
 *
 * 3. OAuth (via plugin)
 *    - Install OAuth plugin for token-based auth
 */

// Example with fetch API and cookie auth
fetch('/wp-json/sermon-browser/v1/sermons', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        title: 'New Sermon',
        sermon_date: '2024-01-15'
    })
});

// Example with cURL and application password
// curl -u "username:application_password" \
//   -X POST https://example.com/wp-json/sermon-browser/v1/sermons \
//   -H "Content-Type: application/json" \
//   -d '{"title":"New Sermon","sermon_date":"2024-01-15"}'
```

## Error Responses

All endpoints return standard WordPress REST API error responses:

```php { .api }
/**
 * Error response format
 *
 * HTTP Status: 400, 401, 403, 404, 500, etc.
 *
 * Response body:
 * {
 *   "code": "error_code",
 *   "message": "Human-readable error message",
 *   "data": {
 *     "status": 400
 *   }
 * }
 */

// Common error codes:
// - rest_forbidden: Insufficient permissions
// - rest_invalid_param: Invalid parameter value
// - rest_not_found: Resource not found
// - rest_rate_limit_exceeded: Rate limit exceeded
// - rest_missing_callback_param: Required parameter missing
```

## Pagination

List endpoints support pagination with standard headers:

```php { .api }
/**
 * Pagination headers (included in response)
 *
 * X-WP-Total: Total number of items
 * X-WP-TotalPages: Total number of pages
 *
 * Link header with rel="next" and rel="prev" URLs
 */

// Example response headers:
// X-WP-Total: 150
// X-WP-TotalPages: 15
// Link: <https://example.com/wp-json/sermon-browser/v1/sermons?page=2>; rel="next"
```

## CORS Support

CORS is handled by WordPress core. Enable with:

```php { .api }
// In wp-config.php or theme functions.php
add_filter('rest_pre_serve_request', function($served, $result, $request) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
    return $served;
}, 10, 3);
```

## JavaScript Example

```javascript
// Fetch sermons with filtering
async function getSermons(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(
        `/wp-json/sermon-browser/v1/sermons?${params}`
    );
    return await response.json();
}

// Create a new sermon
async function createSermon(sermonData) {
    const response = await fetch('/wp-json/sermon-browser/v1/sermons', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce
        },
        body: JSON.stringify(sermonData)
    });
    return await response.json();
}

// Usage
const sermons = await getSermons({ preacher: 5, per_page: 20 });
const newSermon = await createSermon({
    title: 'Grace and Truth',
    sermon_date: '2024-01-15',
    preacher: 5,
    series: 12
});
```
