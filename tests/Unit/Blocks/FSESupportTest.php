<?php

/**
 * Tests for FSESupport.
 *
 * @package SermonBrowser\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Blocks;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Blocks\FSESupport;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

/**
 * Test FSESupport functionality.
 */
class FSESupportTest extends TestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton for each test.
        FSESupport::reset();
    }

    /**
     * Test getInstance returns singleton instance.
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = FSESupport::getInstance();
        $instance2 = FSESupport::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test reset clears the singleton instance.
     */
    public function testResetClearsSingleton(): void
    {
        $instance1 = FSESupport::getInstance();
        FSESupport::reset();
        $instance2 = FSESupport::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test init registers WordPress hooks.
     */
    public function testInitRegistersHooks(): void
    {
        $instance = FSESupport::getInstance();

        Actions\expectAdded('init')
            ->once()
            ->with([$instance, 'registerTemplateParts']);

        Filters\expectAdded('wp_theme_json_data_theme')
            ->once()
            ->with([$instance, 'addThemeJsonSettings']);

        Filters\expectAdded('allowed_block_types_all')
            ->once();

        Filters\expectAdded('get_block_templates')
            ->once();

        Filters\expectAdded('get_block_file_template')
            ->once();

        $instance->init();
        $this->addToAssertionCount(1);
    }

    /**
     * Test allowBlocksInQueryLoop returns true when all blocks allowed.
     */
    public function testAllowBlocksInQueryLoopReturnsTrueWhenAllAllowed(): void
    {
        $instance = FSESupport::getInstance();

        $result = $instance->allowBlocksInQueryLoop(true, null);

        $this->assertTrue($result);
    }

    /**
     * Test allowBlocksInQueryLoop merges sermon blocks with existing blocks.
     */
    public function testAllowBlocksInQueryLoopMergesBlocks(): void
    {
        $instance = FSESupport::getInstance();

        $existingBlocks = ['core/paragraph', 'core/heading'];
        $result = $instance->allowBlocksInQueryLoop($existingBlocks, null);

        // Should contain existing blocks plus sermon blocks.
        $this->assertContains('core/paragraph', $result);
        $this->assertContains('core/heading', $result);
        $this->assertContains('sermon-browser/tag-cloud', $result);
        $this->assertContains('sermon-browser/single-sermon', $result);
        $this->assertContains('sermon-browser/sermon-list', $result);
        $this->assertContains('sermon-browser/sermon-grid', $result);
    }

    /**
     * Test allowBlocksInQueryLoop returns unmodified value for non-array.
     */
    public function testAllowBlocksInQueryLoopReturnsUnmodifiedForNonArray(): void
    {
        $instance = FSESupport::getInstance();

        $result = $instance->allowBlocksInQueryLoop(false, null);

        $this->assertFalse($result);
    }

    /**
     * Test getCustomCssVariables returns valid CSS.
     */
    public function testGetCustomCssVariablesReturnsValidCss(): void
    {
        $css = FSESupport::getCustomCssVariables();

        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--sb-primary-color', $css);
        $this->assertStringContainsString('--sb-accent-color', $css);
        $this->assertStringContainsString('--sb-card-radius', $css);
        $this->assertStringContainsString('--sb-card-shadow', $css);
        $this->assertStringContainsString('--sb-grid-gap', $css);
    }

    /**
     * Test registerTemplateParts returns early for non-block themes.
     */
    public function testRegisterTemplatePartsReturnsEarlyForNonBlockThemes(): void
    {
        $instance = FSESupport::getInstance();

        Functions\expect('wp_is_block_theme')
            ->once()
            ->andReturn(false);

        // add_filter should NOT be called for template part areas.
        Filters\expectAdded('default_wp_template_part_areas')
            ->never();

        $instance->registerTemplateParts();
        $this->addToAssertionCount(1);
    }

    /**
     * Test registerTemplateParts registers area for block themes.
     */
    public function testRegisterTemplatePartsRegistersAreaForBlockThemes(): void
    {
        $instance = FSESupport::getInstance();

        Functions\expect('wp_is_block_theme')
            ->once()
            ->andReturn(true);

        Filters\expectAdded('default_wp_template_part_areas')
            ->once();

        $instance->registerTemplateParts();
        $this->addToAssertionCount(1);
    }

    /**
     * Test addBlockTemplates returns unmodified for non-block themes.
     */
    public function testAddBlockTemplatesReturnsUnmodifiedForNonBlockThemes(): void
    {
        $instance = FSESupport::getInstance();

        Functions\expect('wp_is_block_theme')
            ->once()
            ->andReturn(false);

        $existing = [new \stdClass()];
        $result = $instance->addBlockTemplates($existing, [], 'wp_template');

        $this->assertSame($existing, $result);
    }

    /**
     * Test getBlockFileTemplate returns null for non-matching IDs.
     */
    public function testGetBlockFileTemplateReturnsNullForNonMatchingIds(): void
    {
        $instance = FSESupport::getInstance();

        $result = $instance->getBlockFileTemplate(null, 'other-plugin//template', 'wp_template');

        $this->assertNull($result);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        FSESupport::reset();
        parent::tearDown();
    }
}
