<?php
/**
 * Tests for SermonPaginationAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\SermonPaginationAjax;
use Brain\Monkey\Actions;

/**
 * Test SermonPaginationAjax functionality.
 */
class SermonPaginationAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var SermonPaginationAjax
     */
    private SermonPaginationAjax $handler;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new SermonPaginationAjax();
    }

    /**
     * Test register adds correct action for sermon list.
     */
    public function testRegisterAddsSermonListAction(): void
    {
        Actions\expectAdded('wp_ajax_sb_sermon_list')
            ->once()
            ->with([$this->handler, 'list']);

        $this->handler->register();
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesSermonNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_sermon_nonce', $nonceProperty->getValue($this->handler));
    }

    /**
     * Test handler requires edit_posts capability.
     */
    public function testRequiresEditPostsCapability(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $capabilityProperty = $reflection->getProperty('capability');
        $capabilityProperty->setAccessible(true);

        $this->assertSame('edit_posts', $capabilityProperty->getValue($this->handler));
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
