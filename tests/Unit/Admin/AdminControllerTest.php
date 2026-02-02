<?php

/**
 * Tests for AdminController class.
 *
 * @package SermonBrowser\Tests\Unit\Admin
 */

declare(strict_types=1);

/**
 * Global namespace block for legacy function stubs.
 * These simulate the functions the render methods delegate to.
 * Must be in global namespace for function_exists() to find them.
 */
namespace {
    if (!function_exists('sb_manage_sermons')) {
        function sb_manage_sermons(): void
        {
            echo 'SERMONS_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_new_sermon')) {
        function sb_new_sermon(): void
        {
            echo 'SERMON_EDITOR_RENDERED';
        }
    }

    if (!function_exists('sb_files')) {
        function sb_files(): void
        {
            echo 'FILES_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_manage_preachers')) {
        function sb_manage_preachers(): void
        {
            echo 'PREACHERS_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_manage_everything')) {
        function sb_manage_everything(): void
        {
            echo 'SERIES_SERVICES_RENDERED';
        }
    }

    if (!function_exists('sb_options')) {
        function sb_options(): void
        {
            echo 'OPTIONS_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_templates')) {
        function sb_templates(): void
        {
            echo 'TEMPLATES_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_uninstall')) {
        function sb_uninstall(): void
        {
            echo 'UNINSTALL_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_help')) {
        function sb_help(): void
        {
            echo 'HELP_PAGE_RENDERED';
        }
    }

    if (!function_exists('sb_japan')) {
        function sb_japan(): void
        {
            echo 'JAPAN_PAGE_RENDERED';
        }
    }
}

/**
 * Test namespace block.
 */
namespace SermonBrowser\Tests\Unit\Admin {
    use SermonBrowser\Tests\TestCase;
    use SermonBrowser\Admin\AdminController;
    use Brain\Monkey\Functions;
    use Brain\Monkey\Actions;
    use ReflectionClass;

    /**
     * Test AdminController functionality.
     */
    class AdminControllerTest extends TestCase
    {
        /**
         * The controller instance.
         *
         * @var AdminController
         */
        private AdminController $controller;

        /**
         * Set up the test.
         */
        protected function setUp(): void
        {
            parent::setUp();
            $this->controller = new AdminController();
        }

        /**
         * Test that MENU_SLUG constant has the expected value.
         */
        public function testMenuSlugConstant(): void
        {
            $this->assertSame('sermon-browser', AdminController::MENU_SLUG);
        }

        /**
         * Test register() adds the admin_menu action.
         */
        public function testRegisterAddsAdminMenuAction(): void
        {
            Actions\expectAdded('admin_menu')
                ->once()
                ->with([$this->controller, 'registerMenus']);

            Actions\expectAdded('admin_init')
                ->once()
                ->with([$this->controller, 'registerAssets']);

            $this->controller->register();
        }

        /**
         * Test registerMenus() adds the main menu page.
         */
        public function testRegisterMenusAddsMainMenuPage(): void
        {
            Functions\expect('plugin_basename')
                ->andReturn('sermon-browser/sermon.php');

            Functions\expect('add_menu_page')
                ->once()
                ->with(
                    'Sermons',
                    'Sermons',
                    'publish_posts',
                    'sermon-browser/sermon.php',
                    [$this->controller, 'renderSermonsPage'],
                    SB_PLUGIN_URL . '/assets/images/sb-icon.png'
                );

            Functions\expect('add_submenu_page')
                ->times(10); // 10 submenu pages

            $this->controller->registerMenus();
        }

        /**
         * Test registerMenus() adds all expected submenu pages.
         */
        public function testRegisterMenusAddsAllSubmenus(): void
        {
            Functions\expect('plugin_basename')
                ->andReturn('sermon-browser/sermon.php');

            Functions\expect('add_menu_page')
                ->once();

            $submenus = [];

            Functions\expect('add_submenu_page')
                ->times(10)
                ->andReturnUsing(function ($parent, $pageTitle, $menuTitle, $cap, $slug, $callback) use (&$submenus) {
                    $submenus[] = [
                        'parent' => $parent,
                        'page_title' => $pageTitle,
                        'menu_title' => $menuTitle,
                        'capability' => $cap,
                        'slug' => $slug,
                    ];
                    return '';
                });

            $this->controller->registerMenus();

            // Verify all expected submenus were registered.
            $expectedSlugs = [
                'sermon-browser/sermon.php',      // Sermons (duplicate of main)
                'sermon-browser/new_sermon.php',  // Add/Edit Sermon
                'sermon-browser/files.php',       // Files
                'sermon-browser/preachers.php',   // Preachers
                'sermon-browser/manage.php',      // Series & Services
                'sermon-browser/options.php',     // Options
                'sermon-browser/templates.php',   // Templates
                'sermon-browser/uninstall.php',   // Uninstall
                'sermon-browser/help.php',        // Help
                'sermon-browser/japan.php',       // Pray for Japan
            ];

            $registeredSlugs = array_column($submenus, 'slug');
            foreach ($expectedSlugs as $slug) {
                $this->assertContains($slug, $registeredSlugs, "Submenu {$slug} should be registered");
            }
        }

        /**
         * Test registerMenus() uses correct capabilities for each submenu.
         */
        public function testRegisterMenusUsesCorrectCapabilities(): void
        {
            Functions\expect('plugin_basename')
                ->andReturn('sermon-browser/sermon.php');

            Functions\expect('add_menu_page')
                ->once();

            $capabilityMap = [];

            Functions\expect('add_submenu_page')
                ->times(10)
                ->andReturnUsing(function ($parent, $pageTitle, $menuTitle, $cap, $slug) use (&$capabilityMap) {
                    $capabilityMap[$slug] = $cap;
                    return '';
                });

            $this->controller->registerMenus();

            // Verify capabilities.
            $this->assertSame('publish_posts', $capabilityMap['sermon-browser/sermon.php']);
            $this->assertSame('publish_posts', $capabilityMap['sermon-browser/new_sermon.php']);
            $this->assertSame('upload_files', $capabilityMap['sermon-browser/files.php']);
            $this->assertSame('manage_categories', $capabilityMap['sermon-browser/preachers.php']);
            $this->assertSame('manage_categories', $capabilityMap['sermon-browser/manage.php']);
            $this->assertSame('manage_options', $capabilityMap['sermon-browser/options.php']);
            $this->assertSame('manage_options', $capabilityMap['sermon-browser/templates.php']);
            $this->assertSame('edit_plugins', $capabilityMap['sermon-browser/uninstall.php']);
            $this->assertSame('publish_posts', $capabilityMap['sermon-browser/help.php']);
            $this->assertSame('publish_posts', $capabilityMap['sermon-browser/japan.php']);
        }

        /**
         * Test registerMenus() shows Edit Sermon when editing.
         */
        public function testRegisterMenusShowsEditSermonWhenEditing(): void
        {
            $_REQUEST['page'] = 'sermon-browser/new_sermon.php';
            $_REQUEST['mid'] = '123';

            Functions\expect('plugin_basename')
                ->andReturn('sermon-browser/sermon.php');

            Functions\expect('add_menu_page')
                ->once();

            $sermonSubmenuTitle = null;

            Functions\expect('add_submenu_page')
                ->times(10)
                ->andReturnUsing(function ($parent, $pageTitle, $menuTitle, $cap, $slug) use (&$sermonSubmenuTitle) {
                    if ($slug === 'sermon-browser/new_sermon.php') {
                        $sermonSubmenuTitle = $pageTitle;
                    }
                    return '';
                });

            $this->controller->registerMenus();

            $this->assertSame('Edit Sermon', $sermonSubmenuTitle);
        }

        /**
         * Test registerMenus() shows Add Sermon when not editing.
         */
        public function testRegisterMenusShowsAddSermonWhenNotEditing(): void
        {
            unset($_REQUEST['page'], $_REQUEST['mid']);

            Functions\expect('plugin_basename')
                ->andReturn('sermon-browser/sermon.php');

            Functions\expect('add_menu_page')
                ->once();

            $sermonSubmenuTitle = null;

            Functions\expect('add_submenu_page')
                ->times(10)
                ->andReturnUsing(function ($parent, $pageTitle, $menuTitle, $cap, $slug) use (&$sermonSubmenuTitle) {
                    if ($slug === 'sermon-browser/new_sermon.php') {
                        $sermonSubmenuTitle = $pageTitle;
                    }
                    return '';
                });

            $this->controller->registerMenus();

            $this->assertSame('Add Sermon', $sermonSubmenuTitle);
        }

        /**
         * Test registerAssets() does not throw exception.
         */
        public function testRegisterAssetsDoesNotThrow(): void
        {
            // This is a placeholder method, just verify it doesn't error.
            $this->controller->registerAssets();
            $this->assertTrue(true);
        }

        /**
         * Test renderSermonsPage() calls sb_manage_sermons.
         *
         * The legacy function is defined in global namespace.
         */
        public function testRenderSermonsPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderSermonsPage();
            $output = ob_get_clean();

            $this->assertSame('SERMONS_PAGE_RENDERED', $output);
        }

        /**
         * Test renderSermonEditorPage() calls sb_new_sermon.
         */
        public function testRenderSermonEditorPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderSermonEditorPage();
            $output = ob_get_clean();

            $this->assertSame('SERMON_EDITOR_RENDERED', $output);
        }

        /**
         * Test renderFilesPage() calls sb_files.
         */
        public function testRenderFilesPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderFilesPage();
            $output = ob_get_clean();

            $this->assertSame('FILES_PAGE_RENDERED', $output);
        }

        /**
         * Test renderPreachersPage() calls sb_manage_preachers.
         */
        public function testRenderPreachersPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderPreachersPage();
            $output = ob_get_clean();

            $this->assertSame('PREACHERS_PAGE_RENDERED', $output);
        }

        /**
         * Test renderSeriesServicesPage() calls sb_manage_everything.
         */
        public function testRenderSeriesServicesPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderSeriesServicesPage();
            $output = ob_get_clean();

            $this->assertSame('SERIES_SERVICES_RENDERED', $output);
        }

        /**
         * Test renderOptionsPage() calls sb_options.
         */
        public function testRenderOptionsPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderOptionsPage();
            $output = ob_get_clean();

            $this->assertSame('OPTIONS_PAGE_RENDERED', $output);
        }

        /**
         * Test renderTemplatesPage() calls sb_templates.
         */
        public function testRenderTemplatesPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderTemplatesPage();
            $output = ob_get_clean();

            $this->assertSame('TEMPLATES_PAGE_RENDERED', $output);
        }

        /**
         * Test renderUninstallPage() calls sb_uninstall.
         */
        public function testRenderUninstallPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderUninstallPage();
            $output = ob_get_clean();

            $this->assertSame('UNINSTALL_PAGE_RENDERED', $output);
        }

        /**
         * Test renderHelpPage() calls sb_help.
         */
        public function testRenderHelpPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderHelpPage();
            $output = ob_get_clean();

            $this->assertSame('HELP_PAGE_RENDERED', $output);
        }

        /**
         * Test renderJapanPage() calls sb_japan.
         */
        public function testRenderJapanPageCallsLegacyFunction(): void
        {
            ob_start();
            $this->controller->renderJapanPage();
            $output = ob_get_clean();

            $this->assertSame('JAPAN_PAGE_RENDERED', $output);
        }

        /**
         * Test private getMainMenuFile method returns correct path.
         */
        public function testGetMainMenuFileReturnsCorrectPath(): void
        {
            Functions\expect('plugin_basename')
                ->once()
                ->with(SB_PLUGIN_DIR . '/sermon-browser/sermon.php')
                ->andReturn('sermon-browser/sermon.php');

            // Use reflection to access private method.
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getMainMenuFile');
            $method->setAccessible(true);

            $result = $method->invoke($this->controller);

            $this->assertSame('sermon-browser/sermon.php', $result);
        }

        /**
         * Test private isEditingSermon returns true when editing.
         */
        public function testIsEditingSermonReturnsTrueWhenEditing(): void
        {
            $_REQUEST['page'] = 'sermon-browser/new_sermon.php';
            $_REQUEST['mid'] = '42';

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('isEditingSermon');
            $method->setAccessible(true);

            $result = $method->invoke($this->controller);

            $this->assertTrue($result);
        }

        /**
         * Test private isEditingSermon returns false when page is wrong.
         */
        public function testIsEditingSermonReturnsFalseWhenWrongPage(): void
        {
            $_REQUEST['page'] = 'some-other-page';
            $_REQUEST['mid'] = '42';

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('isEditingSermon');
            $method->setAccessible(true);

            $result = $method->invoke($this->controller);

            $this->assertFalse($result);
        }

        /**
         * Test private isEditingSermon returns false when mid is missing.
         */
        public function testIsEditingSermonReturnsFalseWhenMidMissing(): void
        {
            $_REQUEST['page'] = 'sermon-browser/new_sermon.php';
            unset($_REQUEST['mid']);

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('isEditingSermon');
            $method->setAccessible(true);

            $result = $method->invoke($this->controller);

            $this->assertFalse($result);
        }

        /**
         * Test private isEditingSermon returns false when page is missing.
         */
        public function testIsEditingSermonReturnsFalseWhenPageMissing(): void
        {
            unset($_REQUEST['page']);
            $_REQUEST['mid'] = '42';

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('isEditingSermon');
            $method->setAccessible(true);

            $result = $method->invoke($this->controller);

            $this->assertFalse($result);
        }

        /**
         * Clean up REQUEST superglobal after tests.
         */
        protected function tearDown(): void
        {
            $_REQUEST = [];
            parent::tearDown();
        }
    }
}
