<?php

/**
 * Tests for AjaxRegistry.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\AjaxRegistry;
use Brain\Monkey\Actions;

/**
 * Test AjaxRegistry functionality.
 */
class AjaxRegistryTest extends TestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton between tests.
        AjaxRegistry::reset();
    }

    /**
     * Test getInstance returns singleton.
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = AjaxRegistry::getInstance();
        $instance2 = AjaxRegistry::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test register adds all expected AJAX actions.
     */
    public function testRegisterAddsAllExpectedActions(): void
    {
        // Preacher actions.
        Actions\expectAdded('wp_ajax_sb_preacher_create')->once();
        Actions\expectAdded('wp_ajax_sb_preacher_update')->once();
        Actions\expectAdded('wp_ajax_sb_preacher_delete')->once();

        // Series actions.
        Actions\expectAdded('wp_ajax_sb_series_create')->once();
        Actions\expectAdded('wp_ajax_sb_series_update')->once();
        Actions\expectAdded('wp_ajax_sb_series_delete')->once();

        // Service actions.
        Actions\expectAdded('wp_ajax_sb_service_create')->once();
        Actions\expectAdded('wp_ajax_sb_service_update')->once();
        Actions\expectAdded('wp_ajax_sb_service_delete')->once();

        // File actions.
        Actions\expectAdded('wp_ajax_sb_file_rename')->once();
        Actions\expectAdded('wp_ajax_sb_file_delete')->once();

        $registry = AjaxRegistry::getInstance();
        $registry->register();
        // Brain/Monkey expectations are verified in tearDown
        $this->addToAssertionCount(1);
    }

    /**
     * Test reset clears singleton.
     */
    public function testResetClearsSingleton(): void
    {
        $instance1 = AjaxRegistry::getInstance();
        AjaxRegistry::reset();
        $instance2 = AjaxRegistry::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        AjaxRegistry::reset();
        parent::tearDown();
    }
}
