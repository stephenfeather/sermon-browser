<?php

/**
 * Shared constants for Sermon Browser plugin.
 *
 * Centralizes repeated string literals to satisfy SonarQube rule php:S1192.
 *
 * @package SermonBrowser
 */

declare(strict_types=1);

namespace SermonBrowser;

/**
 * Plugin-wide constants.
 */
class Constants
{
    // HTML attributes
    public const SELECTED = 'selected="selected"';
    public const CHECKED = 'checked="checked"';

    // UI strings
    public const ALL_FILTER = '[All]';
    public const WIDGET_TAG_CLOUD_TITLE = 'Sermon Browser Tags';
    public const LABEL_FILE_NAME = 'File name';
    public const LABEL_FILE_TYPE = 'File type';

    // Date formats
    public const RFC822_DATE = 'D, d M Y H:i:s O';
    public const DEFAULT_TIME = '00:00';

    // Paths
    public const IMAGES_PATH = 'images/';

    // Admin URLs (relative)
    public const SERMON_PAGE = 'admin.php?page=sermon-browser/sermon.php';
    public const NEW_SERMON_GETID3 = 'admin.php?page=sermon-browser/new_sermon.php&getid3=';

    // REST API namespace
    public const REST_NAMESPACE = 'sermon-browser/v1';

    // WordPress capabilities
    public const CAP_MANAGE_SERMONS = 'edit_posts';

    // REST API error messages
    public const ERR_SERMON_NOT_FOUND = 'Sermon not found.';
    public const ERR_PREACHER_NOT_FOUND = 'Preacher not found.';
    public const ERR_SERIES_NOT_FOUND = 'Series not found.';
    public const ERR_SERVICE_NOT_FOUND = 'Service not found.';
    public const ERR_FILE_NOT_FOUND = 'File not found.';
    public const ERR_TAG_NOT_FOUND = 'Tag not found.';
    public const ERR_NO_PERMISSION = 'You do not have the correct permissions to edit the SermonBrowser options';

    // REST API descriptions
    public const DESC_SERMON_ID = 'Unique identifier for the sermon.';
    public const DESC_PREACHER_ID = 'Unique identifier for the preacher.';
    public const DESC_SERIES_ID = 'Unique identifier for the series.';
    public const DESC_SERVICE_ID = 'Unique identifier for the service.';

    // SQL fragments
    public const SQL_LIMIT = ' LIMIT %d';
    public const SQL_LIMIT_OFFSET = ' LIMIT %d OFFSET %d';

    // Protocol prefixes
    public const HTTP = 'http://';
    public const HTTPS = 'https://';

    // Database table suffixes (appended to WordPress prefix)
    public const TABLE_SERMONS = 'sb_sermons';
    public const TABLE_PREACHERS = 'sb_preachers';
    public const TABLE_SERIES = 'sb_series';
    public const TABLE_SERVICES = 'sb_services';
    public const TABLE_STUFF = 'sb_stuff';
    public const TABLE_BOOKS = 'sb_books';
    public const TABLE_TAGS = 'sb_tags';
    public const TABLE_BOOKS_SERMONS = 'sb_books_sermons';
    public const TABLE_SERMONS_TAGS = 'sb_sermons_tags';
}
