<?php
/**
 * Tests for PreacherAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\PreacherAjax;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;

/**
 * Test PreacherAjax functionality.
 */
class PreacherAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var PreacherAjax
     */
    private PreacherAjax $handler;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new PreacherAjax();
    }

    /**
     * Test register adds correct actions.
     */
    public function testRegisterAddsCorrectActions(): void
    {
        Actions\expectAdded('wp_ajax_sb_preacher_create')
            ->once()
            ->with([$this->handler, 'create']);

        Actions\expectAdded('wp_ajax_sb_preacher_update')
            ->once()
            ->with([$this->handler, 'update']);

        Actions\expectAdded('wp_ajax_sb_preacher_delete')
            ->once()
            ->with([$this->handler, 'delete']);

        $this->handler->register();
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }
}
