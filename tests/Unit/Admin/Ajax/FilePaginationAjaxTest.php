<?php
/**
 * Tests for FilePaginationAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\FilePaginationAjax;
use Brain\Monkey\Actions;

/**
 * Test FilePaginationAjax functionality.
 */
class FilePaginationAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var FilePaginationAjax
     */
    private FilePaginationAjax $handler;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FilePaginationAjax();
    }

    /**
     * Test register adds action for unlinked files.
     */
    public function testRegisterAddsUnlinkedAction(): void
    {
        Actions\expectAdded('wp_ajax_sb_file_unlinked')
            ->once()
            ->with([$this->handler, 'unlinked']);

        Actions\expectAdded('wp_ajax_sb_file_linked')
            ->once()
            ->with([$this->handler, 'linked']);

        Actions\expectAdded('wp_ajax_sb_file_search')
            ->once()
            ->with([$this->handler, 'search']);

        $this->handler->register();
    }

    /**
     * Test register adds action for linked files.
     */
    public function testRegisterAddsLinkedAction(): void
    {
        Actions\expectAdded('wp_ajax_sb_file_unlinked')
            ->once();

        Actions\expectAdded('wp_ajax_sb_file_linked')
            ->once()
            ->with([$this->handler, 'linked']);

        Actions\expectAdded('wp_ajax_sb_file_search')
            ->once();

        $this->handler->register();
    }

    /**
     * Test register adds action for file search.
     */
    public function testRegisterAddsSearchAction(): void
    {
        Actions\expectAdded('wp_ajax_sb_file_unlinked')
            ->once();

        Actions\expectAdded('wp_ajax_sb_file_linked')
            ->once();

        Actions\expectAdded('wp_ajax_sb_file_search')
            ->once()
            ->with([$this->handler, 'search']);

        $this->handler->register();
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesFileNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_file_nonce', $nonceProperty->getValue($this->handler));
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
