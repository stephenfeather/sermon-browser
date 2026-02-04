<?php

/**
 * Plugin Bootstrap.
 *
 * Main bootstrap class for the SermonBrowser plugin.
 * Handles initialization, hook registration, and admin menu setup.
 *
 * @package SermonBrowser
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser;

use SermonBrowser\Http\SecurityHeaders;

/**
 * Class Plugin
 *
 * Singleton bootstrap class for the SermonBrowser plugin.
 */
class Plugin
{
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Plugin version.
     *
     * @var string
     */
    public const VERSION = '0.8.0';

    /**
     * Database schema version.
     *
     * @var string
     */
    public const DATABASE_VERSION = '1.7';

    /**
     * Private constructor for singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Boot the plugin.
     *
     * Main entry point called from sermon.php.
     *
     * @return void
     */
    public static function boot(): void
    {
        self::instance()->init();
    }

    /**
     * Initialize the plugin.
     *
     * Defines constants, loads dependencies, and registers hooks.
     *
     * @return void
     */
    private function init(): void
    {
        // Define legacy constants for backward compatibility.
        $this->defineConstants();

        // Note: HelperFunctions class is PSR-4 autoloaded, wrappers in sermon.php.

        // Register early hooks.
        add_action('plugins_loaded', 'sb_hijack');
        add_action('init', 'sb_sermon_init');
        add_action('widgets_init', 'sb_widget_sermon_init');

        // Register security headers for admin and REST API.
        SecurityHeaders::register();

        // Register admin controller for menu handling (Phase 2).
        if (is_admin()) {
            $adminController = new Admin\AdminController();
            $adminController->register();
        }
    }

    /**
     * Define version constants.
     *
     * Maintains backward compatibility with existing code.
     * Note: Path constants (SB_PLUGIN_DIR, etc.) must be defined
     * by sermon.php before calling Plugin::boot().
     *
     * @return void
     */
    private function defineConstants(): void
    {
        if (!defined('SB_CURRENT_VERSION')) {
            define('SB_CURRENT_VERSION', self::VERSION);
        }

        if (!defined('SB_DATABASE_VERSION')) {
            define('SB_DATABASE_VERSION', self::DATABASE_VERSION);
        }
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get the database version.
     *
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return self::DATABASE_VERSION;
    }

    /**
     * Reset the instance (primarily for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
