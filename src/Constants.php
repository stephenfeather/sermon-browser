<?php

declare(strict_types=1);

namespace SermonBrowser;

/**
 * Centralized constants for the Sermon Browser plugin.
 *
 * This class consolidates string literals used throughout the codebase
 * to avoid duplication and ensure consistency.
 *
 * @since 1.0.0
 */
final class Constants
{
    // =========================================================================
    // Database Table Suffixes
    // =========================================================================
    // These are appended to $wpdb->prefix to form full table names.

    public const TABLE_SERMONS = 'sb_sermons';
    public const TABLE_PREACHERS = 'sb_preachers';
    public const TABLE_SERIES = 'sb_series';
    public const TABLE_SERVICES = 'sb_services';
    public const TABLE_FILES = 'sb_files';
    public const TABLE_STUFF = 'sb_stuff';  // Legacy table name for sermon attachments
    public const TABLE_TAGS = 'sb_tags';
    public const TABLE_BOOKS = 'sb_books';
    public const TABLE_SERMONS_TAGS = 'sb_sermons_tags';
    public const TABLE_BOOKS_SERMONS = 'sb_books_sermons';

    // =========================================================================
    // WordPress Capabilities
    // =========================================================================

    /**
     * Capability required to manage sermons (create, edit, delete).
     */
    public const CAP_MANAGE_SERMONS = 'edit_posts';

    // =========================================================================
    // Option Keys
    // =========================================================================
    // Used with sb_get_option() and sb_update_option().

    public const OPT_UPLOAD_DIR = 'upload_dir';
    public const OPT_UPLOAD_URL = 'upload_url';
    public const OPT_PODCAST_URL = 'podcast_url';
    public const OPT_SERMONS_PER_PAGE = 'sermons_per_page';
    public const OPT_MP3_SHORTCODE = 'mp3_shortcode';
    public const OPT_ESV_API_KEY = 'esv_api_key';
    public const OPT_FILTER_TYPE = 'filter_type';
    public const OPT_FILTER_HIDE = 'filter_hide';
    public const OPT_HIDE_NO_ATTACHMENTS = 'hide_no_attachments';
    public const OPT_FILETYPES = 'filetypes';
    public const OPT_CSS_STYLE = 'css_style';
    public const OPT_SINGLE_TEMPLATE = 'single_template';
    public const OPT_SEARCH_TEMPLATE = 'search_template';
    public const OPT_SHOW_DONATE_REMINDER = 'show_donate_reminder';

    // Import options
    public const OPT_IMPORT_PROMPT = 'import_prompt';
    public const OPT_IMPORT_TITLE = 'import_title';
    public const OPT_IMPORT_ARTIST = 'import_artist';
    public const OPT_IMPORT_ALBUM = 'import_album';
    public const OPT_IMPORT_COMMENTS = 'import_comments';
    public const OPT_IMPORT_FILENAME = 'import_filename';

    // =========================================================================
    // REST API
    // =========================================================================

    public const REST_NAMESPACE = 'sermon-browser/v1';

    // =========================================================================
    // AJAX Actions
    // =========================================================================

    public const AJAX_PREFIX = 'sb_';

    // =========================================================================
    // Defaults
    // =========================================================================

    public const DEFAULT_SERMONS_PER_PAGE = 10;
    public const DEFAULT_PAGINATION_MAX = 100;

    // =========================================================================
    // Prevent instantiation
    // =========================================================================

    private function __construct()
    {
        // Static class - cannot be instantiated
    }
}
