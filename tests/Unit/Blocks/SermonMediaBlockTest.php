<?php

/**
 * Tests for Sermon Media Block render.php.
 *
 * @package SermonBrowser\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Blocks;

use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test Sermon Media Block rendering functionality.
 */
class SermonMediaBlockTest extends TestCase
{
    /**
     * Path to the render.php file.
     */
    private string $renderPath;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->renderPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/render.php';

        // Define ABSPATH if not defined.
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/var/www/html/');
        }
    }

    /**
     * Test render.php file exists.
     */
    public function testRenderFileExists(): void
    {
        $this->assertFileExists($this->renderPath, 'render.php should exist in sermon-media block directory');
    }

    /**
     * Test block.json file exists with correct structure.
     */
    public function testBlockJsonExists(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $this->assertFileExists($blockJsonPath, 'block.json should exist');

        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $this->assertEquals('sermon-browser/sermon-media', $blockJson['name']);
        $this->assertEquals('0.8.0', $blockJson['version']);
        $this->assertArrayHasKey('sermonId', $blockJson['attributes']);
        $this->assertArrayHasKey('useLatest', $blockJson['attributes']);
        $this->assertArrayHasKey('mediaType', $blockJson['attributes']);
        $this->assertArrayHasKey('showDownload', $blockJson['attributes']);
        $this->assertArrayHasKey('playerStyle', $blockJson['attributes']);
        $this->assertArrayHasKey('autoplay', $blockJson['attributes']);
        $this->assertArrayHasKey('showTitle', $blockJson['attributes']);
        $this->assertArrayHasKey('showMeta', $blockJson['attributes']);
    }

    /**
     * Test mediaType attribute has correct enum values.
     */
    public function testMediaTypeAttributeEnum(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $mediaTypeAttr = $blockJson['attributes']['mediaType'];

        $this->assertEquals('audio', $mediaTypeAttr['default']);
        $this->assertContains('audio', $mediaTypeAttr['enum']);
        $this->assertContains('video', $mediaTypeAttr['enum']);
        $this->assertContains('both', $mediaTypeAttr['enum']);
    }

    /**
     * Test playerStyle attribute has correct enum values.
     */
    public function testPlayerStyleAttributeEnum(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $playerStyleAttr = $blockJson['attributes']['playerStyle'];

        $this->assertEquals('default', $playerStyleAttr['default']);
        $this->assertContains('default', $playerStyleAttr['enum']);
        $this->assertContains('minimal', $playerStyleAttr['enum']);
        $this->assertContains('full', $playerStyleAttr['enum']);
    }

    /**
     * Test index.js file exists.
     */
    public function testIndexJsExists(): void
    {
        $indexPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/index.js';
        $this->assertFileExists($indexPath, 'index.js should exist');
    }

    /**
     * Test edit.js file exists.
     */
    public function testEditJsExists(): void
    {
        $editPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/edit.js';
        $this->assertFileExists($editPath, 'edit.js should exist');
    }

    /**
     * Test style.css file exists.
     */
    public function testStyleCssExists(): void
    {
        $stylePath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/style.css';
        $this->assertFileExists($stylePath, 'style.css should exist');
    }

    /**
     * Test style.css contains BEM class names.
     */
    public function testStyleCssContainsBemClasses(): void
    {
        $stylePath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/style.css';
        $styleContent = file_get_contents($stylePath);

        $this->assertStringContainsString('.sb-sermon-media', $styleContent);
        $this->assertStringContainsString('.sb-sermon-media__audio', $styleContent);
        $this->assertStringContainsString('.sb-sermon-media__video', $styleContent);
        $this->assertStringContainsString('.sb-sermon-media__downloads', $styleContent);
        $this->assertStringContainsString('.sb-sermon-media--minimal', $styleContent);
        $this->assertStringContainsString('.sb-sermon-media--default', $styleContent);
        $this->assertStringContainsString('.sb-sermon-media--full', $styleContent);
    }

    /**
     * Test block supports correct alignment options.
     */
    public function testBlockSupportsAlignment(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $this->assertArrayHasKey('align', $blockJson['supports']);
        $this->assertContains('wide', $blockJson['supports']['align']);
        $this->assertContains('full', $blockJson['supports']['align']);
    }

    /**
     * Test block supports spacing options.
     */
    public function testBlockSupportsSpacing(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $this->assertArrayHasKey('spacing', $blockJson['supports']);
        $this->assertTrue($blockJson['supports']['spacing']['margin']);
        $this->assertTrue($blockJson['supports']['spacing']['padding']);
    }

    /**
     * Test block has correct category.
     */
    public function testBlockCategory(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $this->assertEquals('widgets', $blockJson['category']);
    }

    /**
     * Test block has format-video icon.
     */
    public function testBlockIcon(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $this->assertEquals('format-video', $blockJson['icon']);
    }

    /**
     * Test block has correct keywords.
     */
    public function testBlockKeywords(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $keywords = $blockJson['keywords'];

        $this->assertContains('sermon', $keywords);
        $this->assertContains('audio', $keywords);
        $this->assertContains('video', $keywords);
        $this->assertContains('media', $keywords);
        $this->assertContains('player', $keywords);
    }

    /**
     * Test block uses file render.
     */
    public function testBlockUsesFileRender(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $this->assertEquals('file:./render.php', $blockJson['render']);
    }

    /**
     * Test default attribute values.
     */
    public function testDefaultAttributeValues(): void
    {
        $blockJsonPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/block.json';
        $blockJson = json_decode(file_get_contents($blockJsonPath), true);

        $attrs = $blockJson['attributes'];

        $this->assertEquals(0, $attrs['sermonId']['default']);
        $this->assertFalse($attrs['useLatest']['default']);
        $this->assertEquals('audio', $attrs['mediaType']['default']);
        $this->assertTrue($attrs['showDownload']['default']);
        $this->assertEquals('default', $attrs['playerStyle']['default']);
        $this->assertFalse($attrs['autoplay']['default']);
        $this->assertFalse($attrs['showTitle']['default']);
        $this->assertFalse($attrs['showMeta']['default']);
    }

    /**
     * Test edit.js imports SermonPicker component.
     */
    public function testEditJsImportsSermonPicker(): void
    {
        $editPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/edit.js';
        $editContent = file_get_contents($editPath);

        $this->assertStringContainsString("from '../single-sermon/components/sermon-picker'", $editContent);
    }

    /**
     * Test edit.js contains inspector controls.
     */
    public function testEditJsContainsInspectorControls(): void
    {
        $editPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/edit.js';
        $editContent = file_get_contents($editPath);

        $this->assertStringContainsString('InspectorControls', $editContent);
        $this->assertStringContainsString('PanelBody', $editContent);
        $this->assertStringContainsString('ToggleControl', $editContent);
        $this->assertStringContainsString('SelectControl', $editContent);
    }

    /**
     * Test index.js registers the block correctly.
     */
    public function testIndexJsRegistersBlock(): void
    {
        $indexPath = dirname(__DIR__, 3) . '/src/Blocks/sermon-media/index.js';
        $indexContent = file_get_contents($indexPath);

        $this->assertStringContainsString('registerBlockType', $indexContent);
        $this->assertStringContainsString("import Edit from './edit'", $indexContent);
        $this->assertStringContainsString("import metadata from './block.json'", $indexContent);
        $this->assertStringContainsString("import './style.css'", $indexContent);
    }
}
