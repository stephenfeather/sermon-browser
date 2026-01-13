<?php
/**
 * PHPUnit bootstrap file for Sermon Browser tests.
 *
 * Uses Brain Monkey and Mockery to mock WordPress functions,
 * avoiding the need for a full WordPress installation during testing.
 *
 * @package SermonBrowser\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define WordPress constants that the plugin expects.
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Plugin-specific constants.
if (!defined('SB_CURRENT_VERSION')) {
    define('SB_CURRENT_VERSION', '0.46.0-dev');
}

if (!defined('SB_PLUGIN_URL')) {
    define('SB_PLUGIN_URL', 'http://localhost/wp-content/plugins/sermon-browser');
}

if (!defined('SB_PLUGIN_DIR')) {
    define('SB_PLUGIN_DIR', dirname(__DIR__));
}

if (!defined('SB_INCLUDES_DIR')) {
    define('SB_INCLUDES_DIR', dirname(__DIR__) . '/sb-includes');
}

if (!defined('SB_ABSPATH')) {
    define('SB_ABSPATH', '/var/www/html/');
}

if (!defined('IS_MU')) {
    define('IS_MU', false);
}
