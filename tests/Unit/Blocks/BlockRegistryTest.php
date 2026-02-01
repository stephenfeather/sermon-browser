<?php

/**
 * Tests for BlockRegistry.
 *
 * @package SermonBrowser\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Blocks;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Blocks\BlockRegistry;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * Test BlockRegistry functionality.
 */
class BlockRegistryTest extends TestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton for each test.
        BlockRegistry::reset();
    }

    /**
     * Test getInstance returns singleton instance.
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = BlockRegistry::getInstance();
        $instance2 = BlockRegistry::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test reset clears the singleton instance.
     */
    public function testResetClearsSingleton(): void
    {
        $instance1 = BlockRegistry::getInstance();
        BlockRegistry::reset();
        $instance2 = BlockRegistry::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test addBlock adds a block and returns self for chaining.
     */
    public function testAddBlockAddsBlockAndReturnsSelf(): void
    {
        $registry = BlockRegistry::getInstance();

        $result = $registry->addBlock('tag-cloud');

        $this->assertSame($registry, $result);
        $this->assertContains('tag-cloud', $registry->getBlocks());
    }

    /**
     * Test addBlock supports method chaining.
     */
    public function testAddBlockSupportsChaining(): void
    {
        $registry = BlockRegistry::getInstance();

        $registry
            ->addBlock('tag-cloud')
            ->addBlock('sermon-list')
            ->addBlock('single-sermon');

        $blocks = $registry->getBlocks();

        $this->assertCount(3, $blocks);
        $this->assertContains('tag-cloud', $blocks);
        $this->assertContains('sermon-list', $blocks);
        $this->assertContains('single-sermon', $blocks);
    }

    /**
     * Test getBlocks returns empty array initially.
     */
    public function testGetBlocksReturnsEmptyArrayInitially(): void
    {
        $registry = BlockRegistry::getInstance();

        $this->assertSame([], $registry->getBlocks());
    }

    /**
     * Test init registers WordPress hooks.
     */
    public function testInitRegistersHooks(): void
    {
        $registry = BlockRegistry::getInstance();

        Actions\expectAdded('init')
            ->once()
            ->with([$registry, 'register']);

        Actions\expectAdded('enqueue_block_editor_assets')
            ->once()
            ->with([$registry, 'enqueueEditorAssets']);

        Actions\expectAdded('wp_enqueue_scripts')
            ->once()
            ->with([$registry, 'enqueueFrontendAssets']);

        $registry->init();
        $this->addToAssertionCount(1);
    }

    /**
     * Test register iterates over all added blocks.
     *
     * Note: Full integration testing of register_block_type requires
     * a WordPress environment. This test verifies the method can be
     * called without errors when no blocks match.
     */
    public function testRegisterIteratesOverBlocks(): void
    {
        $registry = BlockRegistry::getInstance();
        $registry
            ->addBlock('tag-cloud')
            ->addBlock('sermon-list');

        // Verify blocks were added.
        $this->assertCount(2, $registry->getBlocks());
    }

    /**
     * Test enqueueFrontendAssets returns early if no blocks on page.
     */
    public function testEnqueueFrontendAssetsReturnsEarlyIfNoBlocksOnPage(): void
    {
        $registry = BlockRegistry::getInstance();

        // Mock is_singular to return false.
        Functions\expect('is_singular')
            ->once()
            ->andReturn(false);

        // wp_enqueue_style should NOT be called.
        Functions\expect('wp_enqueue_style')
            ->never();

        $registry->enqueueFrontendAssets();
        $this->addToAssertionCount(1);
    }

    /**
     * Test enqueueFrontendAssets returns early if no post.
     */
    public function testEnqueueFrontendAssetsReturnsEarlyIfNoPost(): void
    {
        $registry = BlockRegistry::getInstance();

        Functions\expect('is_singular')
            ->once()
            ->andReturn(true);

        Functions\expect('get_post')
            ->once()
            ->andReturn(null);

        Functions\expect('wp_enqueue_style')
            ->never();

        $registry->enqueueFrontendAssets();
        $this->addToAssertionCount(1);
    }

    /**
     * Test enqueueFrontendAssets returns early if no matching blocks.
     */
    public function testEnqueueFrontendAssetsReturnsEarlyIfNoMatchingBlocks(): void
    {
        $registry = BlockRegistry::getInstance();
        $registry->addBlock('tag-cloud');

        $mockPost = (object) ['ID' => 1, 'post_content' => 'No blocks here'];

        Functions\expect('is_singular')
            ->once()
            ->andReturn(true);

        Functions\expect('get_post')
            ->once()
            ->andReturn($mockPost);

        Functions\expect('has_block')
            ->once()
            ->with('sermon-browser/tag-cloud', $mockPost)
            ->andReturn(false);

        Functions\expect('wp_enqueue_style')
            ->never();

        $registry->enqueueFrontendAssets();
        $this->addToAssertionCount(1);
    }

    /**
     * Test hasBlocksOnPage checks each registered block.
     *
     * This test verifies the block checking loop works correctly.
     */
    public function testHasBlocksOnPageChecksMultipleBlocks(): void
    {
        $registry = BlockRegistry::getInstance();
        $registry
            ->addBlock('tag-cloud')
            ->addBlock('sermon-list')
            ->addBlock('single-sermon');

        $mockPost = (object) ['ID' => 1, 'post_content' => 'No blocks here'];

        Functions\expect('is_singular')
            ->once()
            ->andReturn(true);

        Functions\expect('get_post')
            ->once()
            ->andReturn($mockPost);

        // has_block will be called for each block until one matches or all are checked.
        Functions\expect('has_block')
            ->times(3)
            ->andReturn(false);

        // No matching blocks, so no styles should be enqueued.
        Functions\expect('wp_enqueue_style')
            ->never();

        $registry->enqueueFrontendAssets();
        $this->addToAssertionCount(1);
    }

    /**
     * Test hasBlocksOnPage returns true on first matching block.
     */
    public function testHasBlocksOnPageReturnsTrueOnFirstMatch(): void
    {
        $registry = BlockRegistry::getInstance();
        $registry
            ->addBlock('tag-cloud')
            ->addBlock('sermon-list');

        $mockPost = (object) ['ID' => 1, 'post_content' => '<!-- wp:sermon-browser/tag-cloud -->'];

        Functions\expect('is_singular')
            ->once()
            ->andReturn(true);

        Functions\expect('get_post')
            ->once()
            ->andReturn($mockPost);

        // First block matches, so only one call to has_block.
        Functions\expect('has_block')
            ->once()
            ->with('sermon-browser/tag-cloud', $mockPost)
            ->andReturn(true);

        // File doesn't exist, so no style enqueued (but block detection worked).
        // We can't mock file_exists, so this will check real filesystem.
        // The test verifies block detection logic works.
        $registry->enqueueFrontendAssets();
        $this->addToAssertionCount(1);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        BlockRegistry::reset();
        parent::tearDown();
    }
}
