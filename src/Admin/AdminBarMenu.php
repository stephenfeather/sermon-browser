<?php

declare(strict_types=1);

namespace SermonBrowser\Admin;

use WP_Admin_Bar;

/**
 * Admin Bar Menu for Sermon Browser.
 *
 * Adds sermon browser menu items to the WordPress admin bar
 * for quick access to sermon management functionality.
 *
 * @since 1.0.0
 */
final class AdminBarMenu
{
    /**
     * Add sermon browser menu to admin bar.
     *
     * Adds a main "Sermons" menu with submenus for various sermon
     * browser admin pages based on user capabilities.
     *
     * @return void
     */
    public static function register(): void
    {
        global $wp_admin_bar;

        if (!current_user_can('edit_posts') || !class_exists('WP_Admin_Bar')) {
            return;
        }

        if (self::isViewingSingleSermon()) {
            self::addEditSermonMenu($wp_admin_bar);
        } else {
            self::addSermonsMenu($wp_admin_bar);
        }

        self::addSubmenus($wp_admin_bar);
        self::addNewContentMenuItem($wp_admin_bar);
    }

    /**
     * Check if viewing a single sermon page.
     *
     * @return bool
     */
    private static function isViewingSingleSermon(): bool
    {
        return isset($_GET['sermon_id']) && (int) $_GET['sermon_id'] !== 0 && current_user_can('publish_pages');
    }

    /**
     * Add Edit Sermon menu for single sermon view.
     *
     * @param WP_Admin_Bar $adminBar The admin bar instance.
     *
     * @return void
     */
    private static function addEditSermonMenu(WP_Admin_Bar $adminBar): void
    {
        $sermonId = (int) $_GET['sermon_id'];

        $adminBar->add_menu([
            'id' => 'sermon-browser-menu',
            'title' => __('Edit Sermon', 'sermon-browser'),
            'href' => admin_url('admin.php?page=sermon-browser/new_sermon.php&mid=' . $sermonId),
        ]);

        $adminBar->add_menu([
            'parent' => 'sermon-browser-menu',
            'id' => 'sermon-browser-sermons',
            'title' => __('List Sermons', 'sermon-browser'),
            'href' => admin_url('admin.php?page=sermon-browser/sermon.php'),
        ]);
    }

    /**
     * Add main Sermons menu.
     *
     * @param WP_Admin_Bar $adminBar The admin bar instance.
     *
     * @return void
     */
    private static function addSermonsMenu(WP_Admin_Bar $adminBar): void
    {
        $adminBar->add_menu([
            'id' => 'sermon-browser-menu',
            'title' => __('Sermons', 'sermon-browser'),
            'href' => admin_url('admin.php?page=sermon-browser/sermon.php'),
        ]);

        if (current_user_can('publish_pages')) {
            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-add',
                'title' => __('Add Sermon', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/new_sermon.php'),
            ]);
        }
    }

    /**
     * Add submenus based on user capabilities.
     *
     * @param WP_Admin_Bar $adminBar The admin bar instance.
     *
     * @return void
     */
    private static function addSubmenus(WP_Admin_Bar $adminBar): void
    {
        if (current_user_can('upload_files')) {
            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-files',
                'title' => __('Files', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/files.php'),
            ]);
        }

        if (current_user_can('manage_categories')) {
            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-preachers',
                'title' => __('Preachers', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/preachers.php'),
            ]);

            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-series',
                'title' => __('Series &amp; Services', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/manage.php'),
            ]);
        }

        if (current_user_can('manage_options')) {
            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-options',
                'title' => __('Options', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/options.php'),
            ]);

            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-templates',
                'title' => __('Templates', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/templates.php'),
            ]);
        }

        if (current_user_can('edit_plugins')) {
            $adminBar->add_menu([
                'parent' => 'sermon-browser-menu',
                'id' => 'sermon-browser-uninstall',
                'title' => __('Uninstall', 'sermon-browser'),
                'href' => admin_url('admin.php?page=sermon-browser/uninstall.php'),
            ]);
        }

        // Help and Pray for Japan are available to all users who can edit posts.
        $adminBar->add_menu([
            'parent' => 'sermon-browser-menu',
            'id' => 'sermon-browser-help',
            'title' => __('Help', 'sermon-browser'),
            'href' => admin_url('admin.php?page=sermon-browser/help.php'),
        ]);

        $adminBar->add_menu([
            'parent' => 'sermon-browser-menu',
            'id' => 'sermon-browser-japan',
            'title' => __('Pray for Japan', 'sermon-browser'),
            'href' => admin_url('admin.php?page=sermon-browser/japan.php'),
        ]);
    }

    /**
     * Add Sermon item to the "New" content menu.
     *
     * @param WP_Admin_Bar $adminBar The admin bar instance.
     *
     * @return void
     */
    private static function addNewContentMenuItem(WP_Admin_Bar $adminBar): void
    {
        $adminBar->add_menu([
            'parent' => 'new-content',
            'id' => 'sermon-browser-add2',
            'title' => __('Sermon', 'sermon-browser'),
            'href' => admin_url('admin.php?page=sermon-browser/new_sermon.php'),
        ]);
    }

    // =========================================================================
    // Prevent instantiation
    // =========================================================================

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
        // Static class - cannot be instantiated
    }
}
