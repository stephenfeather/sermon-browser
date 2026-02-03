<?php

/*
Plugin Name: Sermon Browser
Plugin URI: http://www.sermonbrowser.com/
Description: Upload sermons to your website, where they can be searched, listened to, and downloaded. Easy to use with comprehensive help and tutorials.
Author: Mark Barnes
Text Domain: sermon-browser
Version: 0.6.0
Author URI: https://www.markbarnes.net/
Requires at least: 6.0
Requires PHP: 8.0

Copyright (c) 2008-2018 Mark Barnes

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Architecture (PSR-4)
====================
sermon.php          - Entry point. Bootstraps autoloader, defines constants, registers hooks.
src/                - All PHP classes organized by namespace (SermonBrowser\*)
  Admin/            - Admin pages, assets, notices, dashboard widgets
  Ajax/             - AJAX handlers (AjaxRegistry, LegacyAjaxHandler)
  Config/           - Constants, FileTypes configuration
  Facades/          - Static facades for repositories (Sermon, Preacher, Series, etc.)
  Frontend/         - Frontend rendering, widgets, URL building, pagination
  Install/          - Installer, Upgrader, Uninstaller, DefaultTemplates
  Podcast/          - Podcast feed generation (PodcastFeed, PodcastHelper)
  Repositories/     - Database access layer (AbstractRepository + entity repositories)
  Templates/        - Template engine (TemplateEngine, TagParser, TagRenderer)
  Utilities/        - Helper functions, Container
  Widgets/          - WordPress widgets (SermonsWidget, TagCloudWidget, PopularWidget)

Entry points:
- sb_sermon_init()  - Main initialization, registers actions/filters
- sb_hijack()       - Early request interception (downloads, AJAX, CSS)
- sb_shortcode()    - Frontend shortcode output

*/

use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Admin\Ajax\AjaxRegistry;
use SermonBrowser\Templates\TemplateEngine;
use SermonBrowser\Utilities\HelperFunctions;
use SermonBrowser\Ajax\LegacyAjaxHandler;
use SermonBrowser\Frontend\StyleOutput;
use SermonBrowser\Podcast\PodcastFeed;
use SermonBrowser\Podcast\PodcastHelper;
use SermonBrowser\Install\Upgrader;
use SermonBrowser\Install\Installer;
use SermonBrowser\Install\DefaultTemplates;
use SermonBrowser\Http\RequestInterceptor;
// Frontend classes
use SermonBrowser\Frontend\Widgets\PopularWidget;
use SermonBrowser\Frontend\Widgets\SermonWidget;
use SermonBrowser\Frontend\Widgets\TagCloudWidget;
use SermonBrowser\Frontend\BibleText;
use SermonBrowser\Frontend\TemplateHelper;
use SermonBrowser\Frontend\UrlBuilder;
use SermonBrowser\Frontend\AssetLoader;
use SermonBrowser\Frontend\FileDisplay;
use SermonBrowser\Frontend\Pagination;
use SermonBrowser\Frontend\PageTitle;
use SermonBrowser\Frontend\FilterRenderer;
// Admin classes
use SermonBrowser\Admin\Pages\FilesPage;
use SermonBrowser\Admin\Pages\HelpPage;
use SermonBrowser\Admin\Pages\OptionsPage;
use SermonBrowser\Admin\Pages\PreachersPage;
use SermonBrowser\Admin\Pages\SeriesServicesPage;
use SermonBrowser\Admin\Pages\SermonEditorPage;
use SermonBrowser\Admin\Pages\SermonsPage;
use SermonBrowser\Admin\Pages\TemplatesPage;
use SermonBrowser\Admin\Pages\UninstallPage;
use SermonBrowser\Admin\FileSync;
use SermonBrowser\Admin\FormHelpers;
use SermonBrowser\Admin\HelpTabs;
use SermonBrowser\Admin\TagCleanup;
use SermonBrowser\Admin\UploadHelper;
use SermonBrowser\Admin\AdminAssets;
use SermonBrowser\Admin\AdminNotices;
use SermonBrowser\Admin\DashboardWidget;
use SermonBrowser\Admin\AdminBarMenu;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Config\Defaults;
use SermonBrowser\Constants;
use SermonBrowser\Frontend\PageResolver;

/**
* Initialisation
*
* Sets version constants and basic Wordpress hooks.
* @package common_functions
*/

define('SB_CURRENT_VERSION', '0.6.0');
define('SB_DATABASE_VERSION', '1.7');

// Load Composer autoloader for modern PSR-4 classes.
// Must be loaded before sb_define_constants() which uses HelperFunctions.
require_once __DIR__ . '/vendor/autoload.php';

sb_define_constants();

add_action('plugins_loaded', 'sb_hijack');
add_action('init', 'sb_sermon_init');
add_action('widgets_init', 'sb_widget_sermon_init');

// Register modern AJAX handlers (Phase 3: Ajax Modularization).
add_action('init', function () {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        AjaxRegistry::getInstance()->register();
    }
});

// Register REST API routes for Gutenberg blocks (Phase 5).
add_action('plugins_loaded', function () {
    $registry = \SermonBrowser\REST\RestApiRegistry::getInstance();
    $registry->addController(new \SermonBrowser\REST\Endpoints\SermonsController());
    $registry->addController(new \SermonBrowser\REST\Endpoints\TagsController());
    $registry->addController(new \SermonBrowser\REST\Endpoints\PreachersController());
    $registry->addController(new \SermonBrowser\REST\Endpoints\SeriesController());
    $registry->addController(new \SermonBrowser\REST\Endpoints\ServicesController());
    $registry->addController(new \SermonBrowser\REST\Endpoints\FilesController());
    $registry->addController(new \SermonBrowser\REST\Endpoints\SearchController());
    $registry->init();
}, 15);

// Register Gutenberg blocks (Phase 5).
add_action('plugins_loaded', function () {
    $registry = \SermonBrowser\Blocks\BlockRegistry::getInstance();
    $registry->addBlock('tag-cloud');
    $registry->addBlock('single-sermon');
    $registry->addBlock('sermon-list');
    $registry->addBlock('preacher-list');
    $registry->addBlock('series-grid');
    $registry->addBlock('sermon-player');
    $registry->init();
}, 20);

// Phase 6: Template migration on plugin activation.
register_activation_hook(__FILE__, function () {
    $migrator = new \SermonBrowser\Templates\TemplateMigrator();
    $result = $migrator->migrate();
    set_transient('sb_migration_result', $result, HOUR_IN_SECONDS);
});

// Phase 6: Display template migration result as admin notice.
add_action('admin_notices', function () {
    $result = get_transient('sb_migration_result');
    if (!$result) {
        return;
    }

    if ($result->isSuccess()) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . esc_html($result->getMessage()) . '</p>';
        echo '</div>';
    } elseif ($result->hasWarnings()) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . esc_html($result->getMessage()) . '</p>';
        echo '</div>';
    }

    delete_transient('sb_migration_result');
});

/**
* Display podcast, or download linked files
*
* Intercepts Wordpress at earliest opportunity. Checks whether the following are required before the full framework is loaded:
* Ajax data, stylesheet, file download
*/
function sb_hijack()
{
    RequestInterceptor::intercept();
}

/**
* Main initialisation function
*
* Sets up most Wordpress hooks and filters, depending on whether request is for front or back end.
*/
function sb_sermon_init()
{
    global $defaultMultiForm, $defaultSingleForm, $defaultStyle;

    // Load translations
    $textdomain_path = IS_MU ? 'languages' : 'sermon-browser/languages';
    load_plugin_textdomain('sermon-browser', '', $textdomain_path);

    // Set locale if available
    $locale_string = sb_get_locale_string();
    if (!empty($locale_string)) {
        setlocale(LC_ALL, $locale_string);
    }

    // Display the podcast if requested
    if (isset($_GET['podcast'])) {
        PodcastFeed::render();
    }

    // Register stylesheet
    sb_register_styles();

    // Register [sermon] shortcode handler
    add_shortcode('sermons', 'sb_shortcode');
    add_shortcode('sermon', 'sb_shortcode');

    // Configure PHP limits
    sb_configure_php_ini();

    // Check for upgrades (admin only)
    sb_check_admin_upgrades();

    // Load shared (admin/frontend) features
    add_action('save_post', 'sb_update_podcast_url');

    // Register context-specific hooks
    if (!is_admin()) {
        sb_register_frontend_hooks();
    } else {
        sb_register_admin_hooks();
    }
}

/**
 * Register the SermonBrowser stylesheet.
 */
function sb_register_styles()
{
    $style_version = sb_get_option('style_date_modified');
    $base_url = trailingslashit(home_url());

    $style_url = (get_option('permalink_structure') == '')
        ? $base_url . '?sb-style&'
        : $base_url . 'sb-style.css';

    wp_register_style('sb_style', $style_url, false, $style_version);
}

/**
 * Configure PHP ini directives for file uploads.
 */
function sb_configure_php_ini()
{
    if (strpos(ini_get('disable_functions'), 'ini_set') !== false) {
        return;
    }

    $settings = [
        'upload_max_filesize' => ['threshold' => 15360, 'value' => '15M', 'type' => 'kbytes'],
        'post_max_size' => ['threshold' => 15360, 'value' => '15M', 'type' => 'kbytes'],
        'memory_limit' => ['threshold' => 49152, 'value' => '48M', 'type' => 'kbytes'],
        'max_input_time' => ['threshold' => 600, 'value' => '600', 'type' => 'int'],
        'max_execution_time' => ['threshold' => 600, 'value' => '600', 'type' => 'int'],
        'file_uploads' => ['threshold' => '1', 'value' => '1', 'type' => 'string'],
    ];

    foreach ($settings as $key => $config) {
        $current = ini_get($key);
        $below_threshold = match ($config['type']) {
            'kbytes' => sb_return_kbytes($current) < $config['threshold'],
            'int' => intval($current) < $config['threshold'],
            'string' => $current != $config['threshold'],
        };

        if ($below_threshold) {
            ini_set($key, $config['value']);
        }
    }
}

/**
 * Check and run admin upgrades if needed.
 */
function sb_check_admin_upgrades()
{
    if (!current_user_can('manage_options') || !is_admin()) {
        return;
    }

    // Check database version
    $db_version = get_option('sb_sermon_db_version') ?: sb_get_option('db_version');

    if ($db_version && $db_version != SB_DATABASE_VERSION) {
        Upgrader::databaseUpgrade($db_version);
    } elseif (!$db_version) {
        Installer::run();
    }

    // Check code version
    $sb_version = sb_get_option('code_version');
    if ($sb_version != SB_CURRENT_VERSION) {
        Upgrader::versionUpgrade($sb_version, SB_CURRENT_VERSION);
    }

    // Check template version (Phase 6 migration)
    $template_version = sb_get_option('template_version') ?: '0';
    if (version_compare($template_version, '0.6.0', '<')) {
        $migrator = new \SermonBrowser\Templates\TemplateMigrator();
        $result = $migrator->migrate();
        set_transient('sb_migration_result', $result, HOUR_IN_SECONDS);
        sb_update_option('template_version', '0.6.0');
    }
}

/**
 * Register frontend-specific WordPress hooks.
 */
function sb_register_frontend_hooks()
{
    add_action('wp_head', 'sb_add_headers', 0);
    add_action('wp_head', 'wp_print_styles', 9);
    add_action('admin_bar_menu', 'sb_admin_bar_menu', 45);
    add_filter('wp_title', 'sb_page_title');

    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        add_action('wp_footer', 'sb_footer_stats');
    }
}

/**
 * Register admin-specific WordPress hooks.
 */
function sb_register_admin_hooks()
{
    add_action('admin_menu', 'sb_add_pages');
    add_filter('dashboard_glance_items', 'sb_dashboard_glance');
    add_action('admin_enqueue_scripts', 'sb_add_admin_headers');
    add_action('current_screen', 'sb_add_help_tabs');

    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        add_action('admin_footer', 'sb_footer_stats');
    }
}

/**
* Add Sermons menu and sub-menus in admin
*/
function sb_add_pages()
{
    add_menu_page(__('Sermons', 'sermon-browser'), __('Sermons', 'sermon-browser'), 'publish_posts', __FILE__, 'sb_manage_sermons', SB_PLUGIN_URL . '/assets/images/sb-icon.png');
    add_submenu_page(__FILE__, __('Sermons', 'sermon-browser'), __('Sermons', 'sermon-browser'), 'publish_posts', __FILE__, 'sb_manage_sermons');
    if (isset($_REQUEST['page']) && $_REQUEST['page'] == Constants::NEW_SERMON_PAGE && isset($_REQUEST['mid'])) {
        add_submenu_page(__FILE__, __('Edit Sermon', 'sermon-browser'), __('Edit Sermon', 'sermon-browser'), 'publish_posts', Constants::NEW_SERMON_PAGE, 'sb_new_sermon');
    } else {
        add_submenu_page(__FILE__, __('Add Sermon', 'sermon-browser'), __('Add Sermon', 'sermon-browser'), 'publish_posts', Constants::NEW_SERMON_PAGE, 'sb_new_sermon');
    }
    add_submenu_page(__FILE__, __('Files', 'sermon-browser'), __('Files', 'sermon-browser'), 'upload_files', 'sermon-browser/files.php', 'sb_files');
    add_submenu_page(__FILE__, __('Preachers', 'sermon-browser'), __('Preachers', 'sermon-browser'), 'manage_categories', 'sermon-browser/preachers.php', 'sb_manage_preachers');
    add_submenu_page(__FILE__, __('Series &amp; Services', 'sermon-browser'), __('Series &amp; Services', 'sermon-browser'), 'manage_categories', 'sermon-browser/manage.php', 'sb_manage_everything');
    add_submenu_page(__FILE__, __('Options', 'sermon-browser'), __('Options', 'sermon-browser'), 'manage_options', 'sermon-browser/options.php', 'sb_options');
    add_submenu_page(__FILE__, __('Templates', 'sermon-browser'), __('Templates', 'sermon-browser'), 'manage_options', 'sermon-browser/templates.php', 'sb_templates');
    add_submenu_page(__FILE__, __('Uninstall', 'sermon-browser'), __('Uninstall', 'sermon-browser'), 'edit_plugins', 'sermon-browser/uninstall.php', 'sb_uninstall');
    add_submenu_page(__FILE__, __('Help', 'sermon-browser'), __('Help', 'sermon-browser'), 'publish_posts', 'sermon-browser/help.php', 'sb_help');
    add_submenu_page(__FILE__, __('Pray for Japan', 'sermon-browser'), __('Pray for Japan', 'sermon-browser'), 'publish_posts', 'sermon-browser/japan.php', 'sb_japan');
}

/**
 * Converts php.ini mega- or giga-byte numbers into kilobytes.
 *
 * @param string $val Value like '15M' or '1G'.
 * @return int Value in kilobytes.
 */
function sb_return_kbytes($val)
{
    return HelperFunctions::returnKbytes((string) $val);
}

/**
* Count download stats for sermon
*
* Returns the number of plays for a particular file
*
* @param integer $sermonid
* @return integer
*/
function sb_sermon_stats($sermonid)
{
    $stats = File::getTotalDownloadsBySermon((int) $sermonid);
    if ($stats > 0) {
        return $stats;
    }
}

/**
* Updates podcast URL in wp_options
*
* Function required if permalinks changed or [sermons] added to a different page
*/
function sb_update_podcast_url()
{
    global $wp_rewrite;
    $existing_url = sb_get_option('podcast_url');
    if (substr($existing_url, 0, strlen(site_url())) == site_url()) {
        if (sb_display_url() == "") {
            sb_update_option('podcast_url', site_url() . sb_query_char(false) . 'podcast');
        } else {
            sb_update_option('podcast_url', sb_display_url() . sb_query_char(false) . 'podcast');
        }
    }
}

/**
 * Get default values for SermonBrowser.
 *
 * @param string $default_type The type of default.
 * @return mixed The default value.
 */
function sb_get_default($default_type)
{
    return Defaults::get($default_type);
}

/**
 * Returns true if sermons are displayed on the current page.
 *
 * @return bool
 */
function sb_display_front_end()
{
    return PageResolver::displaysFrontEnd();
}

/**
 * Get the page_id of the main sermons page.
 *
 * @return int
 */
function sb_get_page_id()
{
    return PageResolver::getPageId();
}

/**
 * Get the URL of the main sermons page.
 *
 * @return string
 */
function sb_display_url()
{
    return PageResolver::getDisplayUrl();
}

/**
* Adds database statistics to the HTML comments
*
* Requires define('SAVEQUERIES', true) in wp-config.php
* Useful for diagnostics
*/
function sb_footer_stats()
{
    global $wpdb;
    echo '<!-- ';
    echo $wpdb->num_queries . ' queries. ' . timer_stop() . ' seconds.';
    echo chr(13);
    print_r($wpdb->queries);
    echo chr(13);
    echo ' -->';
}

/**
 * Returns the correct string to join the sermonbrowser parameters to the existing URL.
 *
 * @param bool $return_entity Whether to return HTML entity.
 * @return string Either '?', '&', or '&amp;'.
 */
function sb_query_char($return_entity = true)
{
    return PageResolver::getQueryChar($return_entity);
}

/**
* Create the shortcode handler
*
* Standard shortcode handler that inserts the sermonbrowser output into the post/page
*
* @param array $atts
* @return string
*/
function sb_shortcode($atts)
{
    ob_start();
    $atts = shortcode_atts(sb_get_shortcode_defaults(), $atts);

    if ($atts['id'] != '') {
        sb_render_single_sermon($atts);
    } else {
        sb_render_sermon_list($atts);
    }

    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}

/**
 * Get default shortcode attributes with request parameter fallbacks.
 *
 * @return array Default attribute values.
 */
function sb_get_shortcode_defaults()
{
    return [
        'filter' => sb_get_option('filter_type'),
        'filterhide' => sb_get_option('filter_hide'),
        'id' => sb_get_request_int('sermon_id'),
        'preacher' => sb_get_request_int('preacher'),
        'series' => sb_get_request_int('series'),
        'book' => sb_get_request_text('book'),
        'service' => sb_get_request_int('service'),
        'date' => sb_get_request_text('date'),
        'enddate' => sb_get_request_text('enddate'),
        'tag' => sb_get_request_text('stag'),
        'title' => sb_get_request_text('title'),
        'limit' => '0',
        'dir' => sb_get_request_text('dir'),
    ];
}

/**
 * Get an integer value from $_REQUEST or return empty string.
 *
 * @param string $key Request parameter name.
 * @return int|string Integer value or empty string.
 */
function sb_get_request_int($key)
{
    return isset($_REQUEST[$key]) ? (int) $_REQUEST[$key] : '';
}

/**
 * Get a sanitized text value from $_REQUEST or return empty string.
 *
 * @param string $key Request parameter name.
 * @return string Sanitized value or empty string.
 */
function sb_get_request_text($key)
{
    return isset($_REQUEST[$key]) ? sanitize_text_field($_REQUEST[$key]) : '';
}

/**
 * Render a single sermon view.
 *
 * @param array $atts Shortcode attributes.
 */
function sb_render_single_sermon($atts)
{
    // Handle 'latest' shorthand
    $sermon_id = $atts['id'];
    if (strtolower($sermon_id) == 'latest') {
        $atts['id'] = '';
        $query = sb_get_sermons($atts, [], 1, 1);
        $sermon_id = $query[0]->id;
    }

    $sermon = sb_get_single_sermon((int) $sermon_id);

    if (!$sermon) {
        echo '<div class="sermon-browser-results"><span class="error">';
        _e('No sermons found.', 'sermon-browser');
        echo '</span></div>';
        return;
    }

    sb_render_template('single', [
        'Sermon' => $sermon['Sermon'],
        'Files' => $sermon['Files'] ?? [],
        'Code' => $sermon['Code'] ?? [],
        'Tags' => $sermon['Tags'] ?? [],
    ]);
}

/**
 * Render a sermon list view.
 *
 * @param array $atts Shortcode attributes.
 */
function sb_render_sermon_list($atts)
{
    global $record_count;

    $sort_order = sb_resolve_sort_order($atts);
    $page = isset($_REQUEST['pagenum']) ? (int) $_REQUEST['pagenum'] : 1;
    $hide_empty = sb_get_option('hide_no_attachments');
    $sermons = sb_get_sermons($atts, $sort_order, $page, (int) $atts['limit'], $hide_empty);

    sb_render_template('search', [
        'sermons' => $sermons,
        'record_count' => $record_count,
        'atts' => $atts,
    ]);
}

/**
 * Resolve sort order from request and attributes.
 *
 * @param array $atts Shortcode attributes.
 * @return array Sort order with 'by' and 'dir' keys.
 */
function sb_resolve_sort_order($atts)
{
    $sort_criteria = isset($_REQUEST['sortby'])
        ? esc_sql($_REQUEST['sortby'])
        : 'm.datetime';

    if (!empty($atts['dir'])) {
        $dir = esc_sql($atts['dir']);
    } else {
        $dir = ($sort_criteria == 'm.datetime') ? 'desc' : 'asc';
    }

    return ['by' => $sort_criteria, 'dir' => $dir];
}

/**
 * Render a template with error handling.
 *
 * @param string $template Template name ('single' or 'search').
 * @param array  $data     Template data.
 */
function sb_render_template($template, $data)
{
    try {
        $engine = new TemplateEngine();
        echo $engine->render($template, $data);
    } catch (\Exception $e) {
        echo '<div class="sermon-browser-error">' .
            esc_html__('Template error', 'sermon-browser') . '</div>';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- Template error: ' . esc_html($e->getMessage()) . ' -->';
        }
    }
}

/**
 * Registers the Sermon Browser widgets using modern WP_Widget API
 *
 * @since 0.46.0 - Converted from deprecated wp_register_sidebar_widget()
 */
function sb_widget_sermon_init()
{
    // Register modern WP_Widget classes (PSR-4 namespaced)
    register_widget(\SermonBrowser\Widgets\SermonsWidget::class);
    register_widget(\SermonBrowser\Widgets\TagCloudWidget::class);
    register_widget(\SermonBrowser\Widgets\PopularWidget::class);
}

/**
 * Migrate widget settings from old format to new WP_Widget format
 *
 * This function preserves existing widget configurations when upgrading
 * from the deprecated widget API to WP_Widget classes.
 *
 * @since 0.46.0
 */
function sb_migrate_widget_settings()
{
    if (get_option('sb_widget_migration_v046')) {
        return;
    }

    sb_migrate_sermon_widget();
    sb_migrate_popular_widget();

    update_option('sb_widget_migration_v046', true);
}
add_action('admin_init', 'sb_migrate_widget_settings');

/**
 * Migrate sermon widget settings from old format.
 */
function sb_migrate_sermon_widget()
{
    $old_opts = get_option('sb_widget_sermon');
    if (!$old_opts || get_option('widget_sb_sermons')) {
        return;
    }

    $new_opts = ['_multiwidget' => 1];
    $instance_num = 2; // WordPress reserves 1

    foreach ((array) $old_opts as $opts) {
        if (!isset($opts['limit'])) {
            continue;
        }
        $new_opts[$instance_num++] = sb_map_sermon_widget_instance($opts);
    }

    if (count($new_opts) > 1) {
        update_option('widget_sb_sermons', $new_opts);
    }
}

/**
 * Map old sermon widget options to new format.
 *
 * @param array $opts Old widget options.
 * @return array New widget instance options.
 */
function sb_map_sermon_widget_instance($opts)
{
    return [
        'title' => $opts['title'] ?? '',
        'limit' => (int) ($opts['limit'] ?? 5),
        'preacher' => (int) ($opts['preacher'] ?? 0),
        'service' => (int) ($opts['service'] ?? 0),
        'series' => (int) ($opts['series'] ?? 0),
        'show_preacher' => !empty($opts['preacherz']),
        'show_book' => !empty($opts['book']),
        'show_date' => !empty($opts['date']),
    ];
}

/**
 * Migrate popular widget settings from old format.
 */
function sb_migrate_popular_widget()
{
    $old_opts = sb_get_option('popular_widget_options');
    if (!$old_opts || get_option('widget_sb_popular')) {
        return;
    }

    $new_opts = [
        2 => [
            'title' => $old_opts['title'] ?? '',
            'limit' => (int) ($old_opts['limit'] ?? 5),
            'display_sermons' => !empty($old_opts['display_sermons']),
            'display_series' => !empty($old_opts['display_series']),
            'display_preachers' => !empty($old_opts['display_preachers']),
        ],
        '_multiwidget' => 1,
    ];

    update_option('widget_sb_popular', $new_opts);
}

/**
* Wrapper for sb_widget_sermon in frontend.php
*
* Allows main widget functionality to be in the frontend package, whilst still allowing widgets to be modified in admin
* @param array $args
* @param integer $widget_args
*/
function sb_widget_sermon_wrapper($args, $widget_args = 1)
{
    sb_widget_sermon($args, $widget_args);
}

/**
* Wrapper for sb_widget_tag_cloud in frontend.php
*
* Allows main widget functionality to be in the frontend package, whilst still allowing widgets to be modified in admin
* @param array $args
*/
function sb_widget_tag_cloud_wrapper($args)
{
    sb_widget_tag_cloud($args);
}

/**
* Wrapper for sb_widget_popular in frontend.php
*
* Allows main widget functionality to be in the frontend package, whilst still allowing widgets to be modified in admin
* @param array $args
*/
function sb_widget_popular_wrapper($args)
{
    sb_widget_popular($args);
}

/**
 * Get a SermonBrowser option value.
 *
 * Public API - kept for backwards compatibility.
 *
 * @param string $type Option key.
 * @return mixed Option value.
 */
function sb_get_option($type)
{
    return OptionsManager::get($type);
}

/**
 * Update a SermonBrowser option value.
 *
 * Public API - kept for backwards compatibility.
 *
 * @param string $type Option key.
 * @param mixed  $val  Option value.
 * @return bool True if updated.
 */
function sb_update_option($type, $val)
{
    return OptionsManager::update($type, $val);
}

/**
 * Recursive mkdir function with chmod.
 *
 * @param string $pathname Directory path.
 * @param int    $mode     Permission mode.
 * @return bool True on success.
 */
function sb_mkdir($pathname, $mode = 0755)
{
    return HelperFunctions::mkdir($pathname, $mode);
}

/**
* Defines a number of constants used throughout the plugin
*/
function sb_define_constants()
{
    $directories = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
    if ($plugin_dir = $directories[count($directories) - 1] == 'mu-plugins' || is_multisite()) {
        define('IS_MU', true);
    } else {
        define('IS_MU', false);
    }
    if ($directories[count($directories) - 1] == 'mu-plugins') {
        define('SB_PLUGIN_URL', content_url() . '/' . $plugin_dir);
    } else {
        define('SB_PLUGIN_URL', rtrim(content_url() . '/plugins/' . plugin_basename(dirname(__FILE__)), '/'));
    }
    define('SB_PLUGIN_DIR', sb_sanitise_path(defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content') . '/plugins');
    define('SB_WP_CONTENT_DIR', sb_sanitise_path(WP_CONTENT_DIR));
    define('SB_ABSPATH', sb_sanitise_path(ABSPATH));
}

/**
* Returns list of bible books from the database
*
* @return array
*/
function sb_get_bible_books()
{
    return \SermonBrowser\Facades\Book::findAllNames();
}

/**
* Get multiple sermons from the database
*
* Uses Sermon Facade for database operations.
* @param array $filter
* @param array $order
* @param integer $page
* @param integer $limit
* @global integer record_count
* @return array
*/
function sb_get_sermons($filter, $order, $page = 1, $limit = 0, $hide_empty = false)
{
    global $record_count;
    if ($limit == 0) {
        $limit = sb_get_option('sermons_per_page');
    }
    $result = \SermonBrowser\Facades\Sermon::findForFrontendListing(
        (array) $filter,
        (array) $order,
        (int) $page,
        (int) $limit,
        (bool) $hide_empty
    );
    $record_count = $result['total'];
    return $result['items'];
}

/**
* Returns the default time for a particular service
*
* @param integer $service (id in database)
* @return string (service time)
*/
function sb_default_time($service)
{
    $serviceRecord = Service::find((int) $service);
    if ($serviceRecord && isset($serviceRecord->time)) {
        return $serviceRecord->time;
    } else {
        return "00:00";
    }
}

/**
* Gets attachments from database
*
* @param integer $sermon (id in database)
* @param boolean $mp3_only (if true will only return MP3 files)
* @return array
*/
function sb_get_stuff($sermon, $mp3_only = false)
{
    $stuff = File::findBySermon((int) $sermon->id);
    if ($mp3_only) {
        $stuff = array_filter($stuff, fn($f) => str_ends_with($f->name ?? '', '.mp3'));
    }
    $file = $url = $code = array();
    foreach ($stuff as $cur) {
        ${$cur->type}[] = $cur->name;
    }
    return array(
        'Files' => $file,
        'URLs' => $url,
        'Code' => $code,
    );
}

/**
* Increases the download count for file attachments
*
* Increases the download count for the file $stuff_name
*
* @param string $stuff_name
*/
function sb_increase_download_count($stuff_name)
{
    if (!(current_user_can('edit_posts') || current_user_can('publish_posts'))) {
        File::incrementCountByName($stuff_name);
    }
}

/**
 * Output a file in chunks (for large file downloads).
 *
 * @param string $filename Path to the file.
 * @return bool True on success.
 */
function sb_output_file($filename)
{
    return HelperFunctions::outputFile($filename);
}

/**
 * Sanitize Windows paths to use forward slashes.
 *
 * @param string $path The path.
 * @return string Sanitized path.
 */
function sb_sanitise_path($path)
{
    return HelperFunctions::sanitisePath($path);
}

/***************************************
 ** Functions from functions-testable.php **
 **************************************/

/**
 * Generate a random filename suffix for temporary files.
 *
 * @param int $length Number of random characters to generate.
 * @return string Random lowercase letters.
 */
function sb_generate_temp_suffix(int $length = 2): string
{
    return HelperFunctions::generateTempSuffix($length);
}

/**
 * Join an array of passages with a separator.
 *
 * @param array $passages Array of passage strings.
 * @param string $separator Separator between passages.
 * @return string Joined passages.
 */
function sb_join_passages(array $passages, string $separator = ', '): string
{
    return HelperFunctions::joinPassages($passages, $separator);
}

/**
 * Get the locale string for setlocale().
 *
 * @return string Locale string with UTF-8 suffix, or empty if no locale.
 */
function sb_get_locale_string(): string
{
    return HelperFunctions::getLocaleString();
}

/**
 * Check if current user has super admin privileges.
 *
 * @return bool True if user is super admin.
 */
function sb_is_super_admin(): bool
{
    return HelperFunctions::isSuperAdmin();
}

/***************************************
 ** Functions from frontend.php       **
 **************************************/

/**
 * Deprecated function - displays error message
 *
 * @deprecated Use sb_display_sermons() or the sermon browser widget instead
 */
function display_sermons()
{
    echo "This function is now deprecated. Use sb_display_sermons or the sermon browser widget, instead.";
}

/**
 * Display sermons for template use.
 *
 * @param array $options Display options.
 */
function sb_display_sermons($options = array())
{
    SermonWidget::display((array) $options);
}

/**
 * Display the sermon widget in sidebar.
 *
 * @param array     $args        Widget arguments.
 * @param array|int $widget_args Widget instance arguments.
 */
function sb_widget_sermon($args, $widget_args = 1)
{
    SermonWidget::widget($args, $widget_args);
}

/**
 * Display the tag cloud widget in sidebar.
 *
 * @param array $args Widget arguments.
 */
function sb_widget_tag_cloud($args)
{
    TagCloudWidget::widget($args);
}

/**
 * Register admin bar menu.
 */
function sb_admin_bar_menu()
{
    AdminBarMenu::register();
}

/**
 * Sorts an object by rank.
 *
 * @param object $a First object.
 * @param object $b Second object.
 * @return int Comparison result.
 */
function sb_sort_object($a, $b)
{
    if ($a->rank == $b->rank) {
        return 0;
    }
    return ($a->rank < $b->rank) ? -1 : 1;
}

/**
 * Display the most popular sermons widget in sidebar.
 *
 * @param array $args Widget arguments.
 */
function sb_widget_popular($args)
{
    PopularWidget::widget($args);
}

/**
 * Print the most popular widget with default styling.
 */
function sb_print_most_popular()
{
    PopularWidget::printMostPopular();
}

/**
 * Modify page title.
 *
 * @param string $title Current page title.
 * @return string Modified title.
 */
function sb_page_title($title)
{
    return PageTitle::modify((string) $title);
}

/**
 * Downloads external webpage. Used to add Bible passages to sermon page.
 *
 * @param string $page_url The URL to fetch.
 * @param array|string $headers Optional headers.
 * @return string|null The response body.
 */
function sb_download_page($page_url, $headers = array())
{
    return BibleText::downloadPage($page_url, $headers);
}

/**
 * Returns human friendly Bible reference (e.g. John 3:1-16, not John 3:1-John 3:16).
 *
 * @param array $start Start reference with book, chapter, verse keys.
 * @param array $end End reference with book, chapter, verse keys.
 * @param bool $add_link Whether to add filter links to book names.
 * @return string The formatted reference.
 */
function sb_tidy_reference($start, $end, $add_link = false)
{
    return BibleText::tidyReference($start, $end, $add_link);
}

/**
 * Print unstyled bible passage.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return void
 */
function sb_print_bible_passage($start, $end)
{
    BibleText::printBiblePassage($start, $end);
}

/**
 * Returns human friendly Bible reference with link to filter.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return string The formatted reference with links.
 */
function sb_get_books($start, $end)
{
    return BibleText::getBooks($start, $end);
}

/**
 * Add Bible text to single sermon page.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @param string $version Bible version code.
 * @return string The Bible text HTML.
 */
function sb_add_bible_text($start, $end, $version)
{
    return BibleText::addBibleText($start, $end, $version);
}

/**
 * Returns ESV text.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return string The ESV text HTML.
 */
function sb_add_esv_text($start, $end)
{
    return BibleText::addEsvText($start, $end);
}

/**
 * Converts XML string to object.
 *
 * @param string $content The XML string.
 * @return SimpleXMLElement The parsed XML object.
 */
function sb_get_xml($content)
{
    return BibleText::getXml($content);
}

/**
 * Returns NET Bible text.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return string The NET Bible text HTML.
 */
function sb_add_net_text($start, $end)
{
    return BibleText::addNetText($start, $end);
}

/**
 * Returns Bible text using SermonBrowser's own API.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @param string $version Bible version code.
 * @return string The Bible text HTML.
 */
function sb_add_other_bibles($start, $end, $version)
{
    return BibleText::addOtherBibles($start, $end, $version);
}

/**
 * Adds edit sermon link if current user has edit rights.
 *
 * @param int $id Sermon ID.
 */
function sb_edit_link($id)
{
    TemplateHelper::editLink((int) $id);
}

/**
 * Returns URL for search links.
 *
 * @param array $arr URL parameters.
 * @param bool $clear Whether to clear existing parameters.
 * @return string The built URL.
 */
function sb_build_url($arr, $clear = false)
{
    return UrlBuilder::build($arr, $clear);
}

/**
 * Adds javascript and CSS where required.
 */
function sb_add_headers()
{
    AssetLoader::addHeaders();
}

/**
 * Formats date into words.
 *
 * @param object $sermon Sermon object.
 * @return string Formatted date.
 */
function sb_formatted_date($sermon)
{
    return TemplateHelper::formattedDate($sermon);
}

/**
 * Returns podcast URL.
 *
 * @return string Podcast URL.
 */
function sb_podcast_url()
{
    return UrlBuilder::podcastUrl();
}

/**
 * Prints sermon search URL.
 *
 * @param object $sermon Sermon object.
 * @param bool $echo Whether to echo or return.
 * @return string|void The URL if not echoed.
 */
function sb_print_sermon_link($sermon, $echo = true)
{
    $url = UrlBuilder::sermonLink($sermon);
    if ($echo) {
        echo $url;
    } else {
        return $url;
    }
}

/**
 * Prints preacher search URL.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_preacher_link($sermon)
{
    echo UrlBuilder::preacherLink($sermon);
}

/**
 * Prints series search URL.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_series_link($sermon)
{
    echo UrlBuilder::seriesLink($sermon);
}

/**
 * Prints service search URL.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_service_link($sermon)
{
    echo UrlBuilder::serviceLink($sermon);
}

/**
 * Prints bible book search URL.
 *
 * @param string $book_name Book name.
 * @return string The book link.
 */
function sb_get_book_link($book_name)
{
    return UrlBuilder::bookLink($book_name);
}

/**
 * Prints tag search URL.
 *
 * @param string $tag Tag name.
 * @return string The tag link.
 */
function sb_get_tag_link($tag)
{
    return UrlBuilder::tagLink($tag);
}

/**
 * Prints tags.
 *
 * @param array $tags Array of tags.
 */
function sb_print_tags($tags)
{
    TemplateHelper::printTags((array) $tags);
}

/**
 * Prints tag cloud.
 *
 * @param int $minfont Minimum font size.
 * @param int $maxfont Maximum font size.
 */
function sb_print_tag_clouds($minfont = 80, $maxfont = 150)
{
    TemplateHelper::printTagClouds((int) $minfont, (int) $maxfont);
}

/**
 * Prints link to next page.
 *
 * @param int $limit Limit.
 */
function sb_print_next_page_link($limit = 0)
{
    Pagination::printNextPageLink((int) $limit);
}

/**
 * Prints link to previous page.
 *
 * @param int $limit Limit.
 */
function sb_print_prev_page_link($limit = 0)
{
    Pagination::printPrevPageLink((int) $limit);
}

/**
 * Print link to attached files.
 *
 * @param string $url File URL.
 */
function sb_print_url($url)
{
    FileDisplay::printUrl($url);
}

/**
 * Print link to attached external URLs.
 *
 * @param string $url External URL.
 */
function sb_print_url_link($url)
{
    FileDisplay::printUrlLink($url);
}

/**
 * Decode base64 encoded data.
 *
 * @param string $code Base64 encoded code.
 */
function sb_print_code($code)
{
    FileDisplay::printCode($code);
}

/**
 * Prints preacher description.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_preacher_description($sermon)
{
    TemplateHelper::printPreacherDescription($sermon);
}

/**
 * Prints preacher image.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_preacher_image($sermon)
{
    TemplateHelper::printPreacherImage($sermon);
}

/**
 * Prints link to sermon preached next (but not today).
 *
 * @param object $sermon Sermon object.
 */
function sb_print_next_sermon_link($sermon)
{
    TemplateHelper::printNextSermonLink($sermon);
}

/**
 * Prints link to sermon preached on previous days.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_prev_sermon_link($sermon)
{
    TemplateHelper::printPrevSermonLink($sermon);
}

/**
 * Prints links to other sermons preached on the same day.
 *
 * @param object $sermon Sermon object.
 */
function sb_print_sameday_sermon_link($sermon)
{
    TemplateHelper::printSamedaySermonLink($sermon);
}

/**
 * Gets single sermon from the database.
 *
 * @param int $id Sermon ID.
 * @return array|false Sermon data or false.
 */
function sb_get_single_sermon($id)
{
    $id = (int) $id;

    // Get sermon with relations using Facade (includes preacher, service, series)
    $sermon = Sermon::findForTemplate($id);

    if (!$sermon) {
        return false;
    }

    // Handle null series (series_id = 0)
    if ($sermon->ssid === null) {
        $sermon->ssid = 0;
        $sermon->series = '';
    }

    // Get stuff (files and code) using Facade
    $stuff = File::findBySermon($id);
    $file = [];
    $code = [];
    foreach ($stuff as $cur) {
        if ($cur->type === 'file') {
            $file[] = $cur->name;
        } elseif ($cur->type === 'code') {
            $code[] = $cur->name;
        }
    }

    // Get tags using Facade
    $tagObjects = Tag::findBySermon($id);
    $tags = [];
    foreach ($tagObjects as $tag) {
        $tags[] = $tag->name;
    }

    // Unserialize start/end passages
    $sermon->start = unserialize($sermon->start, ['allowed_classes' => false]);
    $sermon->end = unserialize($sermon->end, ['allowed_classes' => false]);

    return [
        'Sermon' => $sermon,
        'Files' => $file,
        'Code' => $code,
        'Tags' => $tags,
    ];
}

/**
 * Prints the filter line for a given parameter.
 *
 * @param string $id Filter ID.
 * @param array $results Results array.
 * @param string $filter Filter type.
 * @param string $display Display type.
 * @param int $max_num Maximum number of items.
 */
function sb_print_filter_line($id, $results, $filter, $display, $max_num = 7)
{
    FilterRenderer::renderLine($id, $results, $filter, $display, $max_num);
}

/**
 * Prints the filter line for the date parameter.
 *
 * @param array $dates Dates array.
 */
function sb_print_date_filter_line($dates)
{
    FilterRenderer::renderDateLine($dates);
}

/**
 * Returns the filter URL minus a given parameter.
 *
 * @param string $param1 First parameter.
 * @param string $param2 Second parameter.
 * @return string URL without the parameter.
 */
function sb_url_minus_parameter($param1, $param2 = '')
{
    return FilterRenderer::urlMinusParameter($param1, $param2);
}

/**
 * Displays the filter on sermon search page.
 *
 * @param array $filter Filter parameters.
 */
function sb_print_filters($filter)
{
    FilterRenderer::render($filter);
}

/**
 * Returns the first MP3 file attached to a sermon.
 * Stats have to be turned off for iTunes compatibility.
 *
 * @param object $sermon Sermon object.
 * @param bool $stats Whether to include stats.
 * @return string|null First MP3 URL or null.
 */
function sb_first_mp3($sermon, $stats = true)
{
    return FileDisplay::firstMp3($sermon, $stats);
}

/***************************************
 ** Functions from admin.php          **
 **************************************/

/**
 * Adds javascript and CSS where required in admin.
 */
function sb_add_admin_headers()
{
    AdminAssets::enqueue();
}

/**
 * Display the options page and handle changes.
 */
function sb_options()
{
    $page = new OptionsPage();
    $page->render();
}

/**
 * Display uninstall screen and perform uninstall if requested.
 */
function sb_uninstall()
{
    $page = new UninstallPage();
    $page->render();
}

/**
 * Display the templates page and handle changes.
 */
function sb_templates()
{
    $page = new TemplatesPage();
    $page->render();
}

/**
 * Display the preachers page and handle changes.
 */
function sb_manage_preachers()
{
    $page = new PreachersPage();
    $page->render();
}

/**
 * Display services & series page and handle changes.
 */
function sb_manage_everything()
{
    $page = new SeriesServicesPage();
    $page->render();
}

/**
 * Display files page and handle changes.
 */
function sb_files()
{
    $page = new FilesPage();
    $page->render();
}

/**
 * Displays Sermons page.
 */
function sb_manage_sermons()
{
    $page = new SermonsPage();
    $page->render();
}

/**
 * Displays new/edit sermon page.
 */
function sb_new_sermon()
{
    $page = new SermonEditorPage();
    $page->render();
}

/**
 * Displays the help page.
 */
function sb_help()
{
    $page = new HelpPage();
    $page->render();
}

/**
 * Displays the Japan prayer page.
 */
function sb_japan()
{
    $page = new HelpPage();
    $page->renderJapan();
}

/**
 * Displays alerts in admin for new users.
 */
function sb_do_alerts()
{
    AdminNotices::render();
}

/**
 * Show the textarea input.
 *
 * @param string $name Textarea name.
 * @param string $html HTML content.
 */
function sb_build_textarea($name, $html)
{
    echo FormHelpers::textarea($name, $html);
}

/**
 * Displays stats in the dashboard.
 */
function sb_rightnow()
{
    DashboardWidget::renderRightNow();
}

/**
 * Displays sermon count in the "At a Glance" dashboard widget.
 *
 * @param array $items Existing glance items.
 * @return array Modified glance items with sermon count.
 */
function sb_dashboard_glance($items)
{
    return DashboardWidget::glanceItems($items);
}

/**
 * Find new files uploaded by FTP.
 */
function sb_scan_dir()
{
    FileSync::sync();
}

/**
 * Check to see if upload folder is writeable.
 *
 * @param string $foldername Folder name.
 * @return string 'writeable/unwriteable/notexist'
 */
function sb_checkSermonUploadable($foldername = "")
{
    return UploadHelper::checkUploadable($foldername);
}

/**
 * Delete any unused tags.
 */
function sb_delete_unused_tags()
{
    TagCleanup::cleanup();
}

/**
 * Returns true if any ID3 import options have been selected.
 *
 * @return boolean
 */
function sb_import_options_set()
{
    return UploadHelper::importOptionsSet();
}

/**
 * Displays notice if ID3 import options have not been set.
 *
 * @param bool $long Whether to show long message.
 */
function sb_print_import_options_message($long = false)
{
    UploadHelper::renderImportMessage($long);
}

/**
 * Echoes the upload form.
 */
function sb_print_upload_form()
{
    UploadHelper::renderForm();
}

/**
 * Add help tabs to SermonBrowser admin pages.
 *
 * @param WP_Screen $screen Current screen object.
 */
function sb_add_help_tabs($screen)
{
    HelpTabs::register($screen);
}

/**
 * Legacy contextual help function.
 *
 * @deprecated Use sb_add_help_tabs instead.
 * @param string $help Help text.
 * @return string Help text unchanged.
 */
function sb_add_contextual_help($help)
{
    _deprecated_function(__FUNCTION__, '0.46.0', 'sb_add_help_tabs');
    return $help;
}

/***************************************
 ** Functions from podcast.php        **
 **************************************/

/**
 * Prints ISO date for podcast pubDate element.
 *
 * @param object|string|int $sermon The sermon object with datetime property, or a datetime string/timestamp.
 * @return void Outputs the formatted date.
 */
function sb_print_iso_date($sermon)
{
    echo PodcastHelper::formatIsoDate($sermon);
}

/**
 * Returns size attribute for enclosure element.
 *
 * @param string $media_name The filename or URL.
 * @param string $media_type The type: 'Files' or 'URLs'.
 * @return string Length attribute string.
 */
function sb_media_size($media_name, $media_type)
{
    return PodcastHelper::getMediaSize($media_name, $media_type);
}

/**
 * Returns duration of .mp3 file.
 *
 * @param string $media_name The filename.
 * @param string $media_type The type: 'Files' or 'URLs'.
 * @return string Duration string or empty.
 */
function sb_mp3_duration($media_name, $media_type)
{
    return PodcastHelper::getMp3Duration($media_name, $media_type);
}

/**
 * Replaces special characters with XML entities.
 *
 * @param string $string The string to encode.
 * @return string The XML-safe encoded string.
 */
function sb_xml_entity_encode($string)
{
    return PodcastHelper::xmlEncode($string);
}

/**
 * Convert filename to URL for podcast feed.
 * Stats are disabled for iTunes compatibility.
 *
 * @param string $media_name The filename or URL.
 * @param string $media_type The type: 'Files' or 'URLs'.
 * @return string The podcast-ready URL.
 */
function sb_podcast_file_url($media_name, $media_type)
{
    return PodcastHelper::getFileUrl($media_name, $media_type);
}

/**
 * Returns correct MIME type attribute for enclosure.
 *
 * @param string $media_name The filename.
 * @return string Type attribute string or empty.
 */
function sb_mime_type($media_name)
{
    return PodcastHelper::getMimeType($media_name);
}

/***************************************
 ** Functions from sb-install.php     **
 **************************************/

/**
 * Run the Sermon Browser installation.
 *
 * Creates database tables and sets default options.
 *
 * @return void
 */
function sb_install()
{
    Installer::run();
}

/**
 * Get the default template for search results (multi-sermon view).
 *
 * @return string The default multi-sermon template HTML.
 */
function sb_default_multi_template()
{
    return DefaultTemplates::multiTemplate();
}

/**
 * Get the default template for single sermon pages.
 *
 * @return string The default single sermon template HTML.
 */
function sb_default_single_template()
{
    return DefaultTemplates::singleTemplate();
}

/**
 * Get the default CSS styles.
 *
 * @return string The default CSS with plugin URL placeholder replaced.
 */
function sb_default_css()
{
    return DefaultTemplates::defaultCss();
}

/**
 * Get the default template for sermon excerpts.
 *
 * @return string The default excerpt template HTML.
 */
function sb_default_excerpt_template()
{
    return DefaultTemplates::excerptTemplate();
}

/***************************************
 ** Functions from upgrade.php        **
 **************************************/

/**
 * Checks for old-style sermonbrowser options (prior to 0.43).
 *
 * @return void
 */
function sb_upgrade_options(): void
{
    Upgrader::upgradeOptions();
}

/**
 * Runs the version upgrade procedures (add options added since last db update).
 *
 * @param string $oldVersion The previous version.
 * @param string $newVersion The new version.
 * @return void
 */
function sb_version_upgrade(string $oldVersion, string $newVersion): void
{
    Upgrader::versionUpgrade($oldVersion, $newVersion);
}

/**
 * Runs the database upgrade procedures (modifies database structure).
 *
 * @param string $oldVersion The previous database version.
 * @return void
 */
function sb_database_upgrade(string $oldVersion): void
{
    Upgrader::databaseUpgrade($oldVersion);
}
