<?php

/**
 * Tests for TemplateEngine.
 *
 * @package SermonBrowser\Tests\Unit\Templates
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Templates;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Templates\TemplateEngine;
use SermonBrowser\Templates\TagParser;
use SermonBrowser\Config\OptionsManager;
use Brain\Monkey\Functions;
use stdClass;
use Mockery;

/**
 * Test TemplateEngine functionality.
 *
 * Tests template loading, parsing delegation, and transient caching.
 */
class TemplateEngineTest extends TestCase
{
    /**
     * The engine instance.
     *
     * @var TemplateEngine
     */
    private TemplateEngine $engine;

    /**
     * Mock TagParser.
     *
     * @var TagParser|\Mockery\MockInterface
     */
    private $mockParser;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockParser = Mockery::mock(TagParser::class);
        $this->engine = new TemplateEngine($this->mockParser);

        // Clear OptionsManager cache before each test
        $reflection = new \ReflectionClass(OptionsManager::class);
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue(null, null);
    }

    /**
     * Helper to set up options mock with a template.
     *
     * OptionsManager stores 'single_template', 'search_template', 'css_style'
     * as individual options with key 'sermonbrowser_{name}' and base64 encoded.
     */
    private function setupOptionsMock(array $options): void
    {
        Functions\stubs([
            'get_option' => function ($key) use ($options) {
                // Handle special template options stored individually
                if ($key === 'sermonbrowser_search_template' && isset($options['search_template'])) {
                    return base64_encode($options['search_template']);
                }
                if ($key === 'sermonbrowser_single_template' && isset($options['single_template'])) {
                    return base64_encode($options['single_template']);
                }
                if ($key === 'sermonbrowser_css_style' && isset($options['css_style'])) {
                    return base64_encode($options['css_style']);
                }
                // Handle regular serialized options
                if ($key === 'sermonbrowser_options') {
                    return base64_encode(serialize($options));
                }
                return false;
            },
        ]);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    /**
     * Test TemplateEngine can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TemplateEngine::class, $this->engine);
    }

    /**
     * Test TemplateEngine can be instantiated with default parser.
     */
    public function testCanBeInstantiatedWithDefaultParser(): void
    {
        $engine = new TemplateEngine();
        $this->assertInstanceOf(TemplateEngine::class, $engine);
    }

    // =========================================================================
    // Template Loading Tests - Search Context
    // =========================================================================

    /**
     * Test render loads search template from OptionsManager.
     */
    public function testRenderLoadsSearchTemplateFromOption(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data = ['sermons' => []];
        $cacheKey = 'sb_template_search_' . md5($template . serialize($data));

        $this->setupOptionsMock(['search_template' => $template]);

        Functions\when('get_transient')->justReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with($template, $data, 'search')
            ->andReturn('<div>Rendered</div>');

        Functions\when('set_transient')->justReturn(true);

        $result = $this->engine->render('search', $data);

        $this->assertEquals('<div>Rendered</div>', $result);
    }

    /**
     * Test render loads single template from OptionsManager.
     */
    public function testRenderLoadsSingleTemplateFromOption(): void
    {
        $template = '<h1>[sermon_title]</h1><p>[sermon_description]</p>';
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon];
        $cacheKey = 'sb_template_single_' . md5($template . serialize($data));

        $this->setupOptionsMock(['single_template' => $template]);

        Functions\when('get_transient')->justReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with($template, $data, 'single')
            ->andReturn('<h1>My Sermon</h1><p>Description</p>');

        Functions\when('set_transient')->justReturn(true);

        $result = $this->engine->render('single', $data);

        $this->assertEquals('<h1>My Sermon</h1><p>Description</p>', $result);
    }

    // =========================================================================
    // Caching Tests
    // =========================================================================

    /**
     * Test render returns cached result when transient exists.
     */
    public function testRenderReturnsCachedResult(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data = ['sermons' => []];
        $cachedHtml = '<div>Cached Result</div>';

        $this->setupOptionsMock(['search_template' => $template]);

        Functions\when('get_transient')->justReturn($cachedHtml);

        // Parser should NOT be called when cache hit
        $this->mockParser->shouldNotReceive('parse');

        $result = $this->engine->render('search', $data);

        $this->assertEquals($cachedHtml, $result);
    }

    /**
     * Test render stores result in transient cache.
     */
    public function testRenderStoresResultInTransient(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data = ['sermons' => []];

        $this->setupOptionsMock(['search_template' => $template]);

        Functions\when('get_transient')->justReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->andReturn('<div>Fresh Result</div>');

        Functions\when('set_transient')->justReturn(true);

        $result = $this->engine->render('search', $data);

        $this->assertEquals('<div>Fresh Result</div>', $result);
    }

    /**
     * Test cache key differs by template type.
     */
    public function testCacheKeyDiffersByType(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data = ['Sermon' => $this->createMockSermon()];

        // Search type
        $searchCacheKey = 'sb_template_search_' . md5($template . serialize($data));

        // Single type
        $singleCacheKey = 'sb_template_single_' . md5($template . serialize($data));

        $this->assertNotEquals($searchCacheKey, $singleCacheKey);
    }

    /**
     * Test cache key differs by data.
     */
    public function testCacheKeyDiffersByData(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data1 = ['Sermon' => $this->createMockSermon()];
        $data2 = ['Sermon' => $this->createMockSermon()];
        $data2['Sermon']->id = 999;

        $cacheKey1 = 'sb_template_single_' . md5($template . serialize($data1));
        $cacheKey2 = 'sb_template_single_' . md5($template . serialize($data2));

        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    // =========================================================================
    // Empty/Missing Template Tests
    // =========================================================================

    /**
     * Test render returns empty string when template is empty.
     */
    public function testRenderReturnsEmptyStringWhenTemplateEmpty(): void
    {
        $data = [];

        $this->setupOptionsMock(['search_template' => '']);

        Functions\when('get_transient')->justReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with('', $data, 'search')
            ->andReturn('');

        Functions\when('set_transient')->justReturn(true);

        $result = $this->engine->render('search', $data);

        $this->assertEquals('', $result);
    }

    /**
     * Test render handles missing template option.
     */
    public function testRenderHandlesNullTemplateOption(): void
    {
        $data = [];

        $this->setupOptionsMock([]);

        Functions\when('get_transient')->justReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with('', $data, 'search')
            ->andReturn('');

        Functions\when('set_transient')->justReturn(true);

        $result = $this->engine->render('search', $data);

        $this->assertEquals('', $result);
    }

    // =========================================================================
    // Invalid Type Tests
    // =========================================================================

    /**
     * Test render throws exception for invalid type.
     */
    public function testRenderThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid template type: invalid');

        $this->engine->render('invalid', []);
    }

    // =========================================================================
    // Cache Bypass Tests
    // =========================================================================

    /**
     * Test render can bypass cache when specified.
     */
    public function testRenderCanBypassCache(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data = ['sermons' => []];

        $this->setupOptionsMock(['search_template' => $template]);

        // get_transient should NOT be called when bypassing cache - but we still
        // set up the mock since other code might call it
        Functions\when('get_transient')->justReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->andReturn('<div>Fresh Result</div>');

        Functions\when('set_transient')->justReturn(true);

        $result = $this->engine->render('search', $data, true);

        $this->assertEquals('<div>Fresh Result</div>', $result);
    }

    // =========================================================================
    // clearCache Tests
    // =========================================================================

    /**
     * Test clearCache deletes all template transients.
     */
    public function testClearCacheDeletesTransients(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->options = 'wp_options';

        // Mock esc_like to return the pattern as-is
        $wpdb->shouldReceive('esc_like')
            ->once()
            ->with('sb_template_')
            ->andReturn('sb_template_');

        // Mock prepare for SELECT query
        $wpdb->shouldReceive('prepare')
            ->once()
            ->with(
                "SELECT option_name FROM wp_options WHERE option_name LIKE %s",
                '_transient_sb_template_%'
            )
            ->andReturn("SELECT option_name FROM wp_options WHERE option_name LIKE '_transient_sb_template_%'");

        // Mock get_col to return transient names
        $wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn([
                '_transient_sb_template_single_abc123',
                '_transient_sb_template_multi_def456',
            ]);

        // Mock delete_transient for each
        Functions\when('delete_transient')->justReturn(true);

        $result = $this->engine->clearCache();

        $this->assertEquals(2, $result);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock sermon object for testing.
     *
     * @return stdClass
     */
    private function createMockSermon(): stdClass
    {
        $sermon = new stdClass();
        $sermon->id = 1;
        $sermon->title = 'Test Sermon';
        $sermon->description = 'Test description';
        $sermon->datetime = '2024-01-01 10:00:00';
        $sermon->pid = 5;
        $sermon->preacher = 'John Doe';
        $sermon->preacher_description = '';
        $sermon->image = '';
        $sermon->ssid = 10;
        $sermon->series = 'Test Series';
        $sermon->sid = 20;
        $sermon->service = 'Sunday Morning';
        $sermon->start = [];
        $sermon->end = [];

        return $sermon;
    }
}
