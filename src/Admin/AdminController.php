<?php

/**
 * Admin Controller.
 *
 * Handles admin menu registration and routing to page handlers.
 *
 * @package SermonBrowser\Admin
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

/**
 * Class AdminController
 *
 * Registers admin menus and routes to appropriate page handlers.
 */
class AdminController
{
    /**
     * The main menu slug.
     *
     * @var string
     */
    public const MENU_SLUG = 'sermon-browser';

    /**
     * Register the admin menu hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_init', [$this, 'registerAssets']);
    }

    /**
     * Register admin menus.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        // Main menu page.
        add_menu_page(
            __('Sermons', 'sermon-browser'),
            __('Sermons', 'sermon-browser'),
            'publish_posts',
            $this->getMainMenuFile(),
            [$this, 'renderSermonsPage'],
            SB_PLUGIN_URL . '/assets/images/sb-icon.png'
        );

        // Sermons submenu (duplicate of main for cleaner display).
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Sermons', 'sermon-browser'),
            __('Sermons', 'sermon-browser'),
            'publish_posts',
            $this->getMainMenuFile(),
            [$this, 'renderSermonsPage']
        );

        // Add/Edit Sermon submenu.
        $sermonTitle = $this->isEditingSermon()
            ? __('Edit Sermon', 'sermon-browser')
            : __('Add Sermon', 'sermon-browser');

        add_submenu_page(
            $this->getMainMenuFile(),
            $sermonTitle,
            $sermonTitle,
            'publish_posts',
            'sermon-browser/new_sermon.php',
            [$this, 'renderSermonEditorPage']
        );

        // Files submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Files', 'sermon-browser'),
            __('Files', 'sermon-browser'),
            'upload_files',
            'sermon-browser/files.php',
            [$this, 'renderFilesPage']
        );

        // Preachers submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Preachers', 'sermon-browser'),
            __('Preachers', 'sermon-browser'),
            'manage_categories',
            'sermon-browser/preachers.php',
            [$this, 'renderPreachersPage']
        );

        // Series & Services submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Series &amp; Services', 'sermon-browser'),
            __('Series &amp; Services', 'sermon-browser'),
            'manage_categories',
            'sermon-browser/manage.php',
            [$this, 'renderSeriesServicesPage']
        );

        // Options submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Options', 'sermon-browser'),
            __('Options', 'sermon-browser'),
            'manage_options',
            'sermon-browser/options.php',
            [$this, 'renderOptionsPage']
        );

        // Templates submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Templates', 'sermon-browser'),
            __('Templates', 'sermon-browser'),
            'manage_options',
            'sermon-browser/templates.php',
            [$this, 'renderTemplatesPage']
        );

        // Uninstall submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Uninstall', 'sermon-browser'),
            __('Uninstall', 'sermon-browser'),
            'edit_plugins',
            'sermon-browser/uninstall.php',
            [$this, 'renderUninstallPage']
        );

        // Help submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Help', 'sermon-browser'),
            __('Help', 'sermon-browser'),
            'publish_posts',
            'sermon-browser/help.php',
            [$this, 'renderHelpPage']
        );

        // Pray for Japan submenu.
        add_submenu_page(
            $this->getMainMenuFile(),
            __('Pray for Japan', 'sermon-browser'),
            __('Pray for Japan', 'sermon-browser'),
            'publish_posts',
            'sermon-browser/japan.php',
            [$this, 'renderJapanPage']
        );
    }

    /**
     * Register admin assets (scripts and styles).
     *
     * @return void
     */
    public function registerAssets(): void
    {
        // Assets are currently handled in sb_add_admin_headers().
        // This method is a placeholder for future asset consolidation.
    }

    /**
     * Get the main menu file path (used as menu slug).
     *
     * Uses the sermon.php file path for backward compatibility
     * with existing URL structures.
     *
     * @return string
     */
    private function getMainMenuFile(): string
    {
        // Return the path WordPress expects for the main plugin file.
        return plugin_basename(SB_PLUGIN_DIR . '/sermon-browser/sermon.php');
    }

    /**
     * Check if we're editing an existing sermon.
     *
     * @return bool
     */
    private function isEditingSermon(): bool
    {
        return isset($_REQUEST['page'])
            && $_REQUEST['page'] === 'sermon-browser/new_sermon.php'
            && isset($_REQUEST['mid']);
    }

    /**
     * Render the Sermons list page.
     *
     * @return void
     */
    public function renderSermonsPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_manage_sermons')) {
            sb_manage_sermons();
        }
    }

    /**
     * Render the Sermon Editor page (Add/Edit).
     *
     * @return void
     */
    public function renderSermonEditorPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_new_sermon')) {
            sb_new_sermon();
        }
    }

    /**
     * Render the Files page.
     *
     * @return void
     */
    public function renderFilesPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_files')) {
            sb_files();
        }
    }

    /**
     * Render the Preachers page.
     *
     * @return void
     */
    public function renderPreachersPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_manage_preachers')) {
            sb_manage_preachers();
        }
    }

    /**
     * Render the Series & Services page.
     *
     * @return void
     */
    public function renderSeriesServicesPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_manage_everything')) {
            sb_manage_everything();
        }
    }

    /**
     * Render the Options page.
     *
     * @return void
     */
    public function renderOptionsPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_options')) {
            sb_options();
        }
    }

    /**
     * Render the Templates page.
     *
     * @return void
     */
    public function renderTemplatesPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_templates')) {
            sb_templates();
        }
    }

    /**
     * Render the Uninstall page.
     *
     * @return void
     */
    public function renderUninstallPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_uninstall')) {
            sb_uninstall();
        }
    }

    /**
     * Render the Help page.
     *
     * @return void
     */
    public function renderHelpPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_help')) {
            sb_help();
        }
    }

    /**
     * Render the Pray for Japan page.
     *
     * @return void
     */
    public function renderJapanPage(): void
    {
        // Delegate to legacy function for now.
        if (function_exists('sb_japan')) {
            sb_japan();
        }
    }
}
