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
     * Test render loads search template from sb_get_option.
     */
    public function testRenderLoadsSearchTemplateFromOption(): void
    {
        $template = '<div>[sermon_title]</div>';
        $data = ['sermons' => []];
        $cacheKey = 'sb_template_search_' . md5($template . serialize($data));

        Functions\expect('get_transient')
            ->once()
            ->with($cacheKey)
            ->andReturn(false);

        Functions\expect('sb_get_option')
            ->once()
            ->with('search_template')
            ->andReturn($template);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with($template, $data, 'search')
            ->andReturn('<div>Rendered</div>');

        Functions\expect('set_transient')
            ->once()
            ->with($cacheKey, '<div>Rendered</div>', Mockery::any())
            ->andReturn(true);

        $result = $this->engine->render('search', $data);

        $this->assertEquals('<div>Rendered</div>', $result);
    }

    /**
     * Test render loads single template from sb_get_option.
     */
    public function testRenderLoadsSingleTemplateFromOption(): void
    {
        $template = '<h1>[sermon_title]</h1><p>[sermon_description]</p>';
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon];
        $cacheKey = 'sb_template_single_' . md5($template . serialize($data));

        Functions\expect('get_transient')
            ->once()
            ->with($cacheKey)
            ->andReturn(false);

        Functions\expect('sb_get_option')
            ->once()
            ->with('single_template')
            ->andReturn($template);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with($template, $data, 'single')
            ->andReturn('<h1>My Sermon</h1><p>Description</p>');

        Functions\expect('set_transient')
            ->once()
            ->with($cacheKey, '<h1>My Sermon</h1><p>Description</p>', Mockery::any())
            ->andReturn(true);

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
        $cacheKey = 'sb_template_search_' . md5($template . serialize($data));
        $cachedHtml = '<div>Cached Result</div>';

        // First, we need to know the template to compute the cache key
        // But the engine checks cache BEFORE loading template
        // So we need to expect sb_get_option to be called for cache key computation
        Functions\expect('sb_get_option')
            ->once()
            ->with('search_template')
            ->andReturn($template);

        Functions\expect('get_transient')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedHtml);

        // Parser should NOT be called when cache hit
        $this->mockParser->shouldNotReceive('parse');

        // set_transient should NOT be called when cache hit
        Functions\expect('set_transient')
            ->never();

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
        $cacheKey = 'sb_template_search_' . md5($template . serialize($data));

        Functions\expect('get_transient')
            ->once()
            ->with($cacheKey)
            ->andReturn(false);

        Functions\expect('sb_get_option')
            ->once()
            ->with('search_template')
            ->andReturn($template);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->andReturn('<div>Fresh Result</div>');

        // Verify set_transient is called with expected arguments
        // Cache duration is 1 hour (3600 seconds)
        Functions\expect('set_transient')
            ->once()
            ->with($cacheKey, '<div>Fresh Result</div>', 3600)
            ->andReturn(true);

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
        $template = '';
        $cacheKey = 'sb_template_search_' . md5($template . serialize($data));

        Functions\expect('sb_get_option')
            ->once()
            ->with('search_template')
            ->andReturn('');

        Functions\expect('get_transient')
            ->once()
            ->with($cacheKey)
            ->andReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with('', $data, 'search')
            ->andReturn('');

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        $result = $this->engine->render('search', $data);

        $this->assertEquals('', $result);
    }

    /**
     * Test render handles null template option.
     */
    public function testRenderHandlesNullTemplateOption(): void
    {
        $data = [];

        Functions\expect('sb_get_option')
            ->once()
            ->with('search_template')
            ->andReturn(null);

        // When template is null/empty, we should still compute cache key with empty string
        $cacheKey = 'sb_template_search_' . md5('' . serialize($data));

        Functions\expect('get_transient')
            ->once()
            ->with($cacheKey)
            ->andReturn(false);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->with('', $data, 'search')
            ->andReturn('');

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

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
        $cacheKey = 'sb_template_search_' . md5($template . serialize($data));

        // get_transient should NOT be called when bypassing cache
        Functions\expect('get_transient')
            ->never();

        Functions\expect('sb_get_option')
            ->once()
            ->with('search_template')
            ->andReturn($template);

        $this->mockParser
            ->shouldReceive('parse')
            ->once()
            ->andReturn('<div>Fresh Result</div>');

        // set_transient should still be called to update cache
        Functions\expect('set_transient')
            ->once()
            ->with($cacheKey, '<div>Fresh Result</div>', 3600)
            ->andReturn(true);

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
        Functions\expect('delete_transient')
            ->twice()
            ->andReturn(true);

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
