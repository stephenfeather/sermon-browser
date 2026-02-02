<?php

/**
 * Tests for Frontend\UrlBuilder class.
 *
 * @package SermonBrowser\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Frontend;

use ReflectionClass;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Frontend\UrlBuilder;
use SermonBrowser\Frontend\PageResolver;

/**
 * Test class for UrlBuilder.
 */
class UrlBuilderTest extends TestCase
{
    /**
     * Original superglobal values for restoration.
     *
     * @var array<string, mixed>
     */
    private array $originalGet;
    private array $originalPost;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Save original superglobals
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;

        // Clear superglobals for clean test state
        $_GET = [];
        $_POST = [];

        // Set PageResolver cached values via reflection
        $this->setPageResolverCache('https://example.com/sermons/', '?');
    }

    /**
     * Restore superglobals and clear cache after each test.
     */
    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;

        // Clear PageResolver cache
        $this->clearPageResolverCache();

        parent::tearDown();
    }

    /**
     * Set PageResolver cached static values.
     *
     * @param string $displayUrl The display URL to cache.
     * @param string $queryChar  The query character to cache.
     */
    private function setPageResolverCache(string $displayUrl, string $queryChar): void
    {
        $reflection = new ReflectionClass(PageResolver::class);

        $displayUrlProp = $reflection->getProperty('displayUrl');
        $displayUrlProp->setValue(null, $displayUrl);

        // PageResolver doesn't have a queryChar property - it's computed
        // We'll need to also set pageId to avoid WP_Query calls
        $pageIdProp = $reflection->getProperty('pageId');
        $pageIdProp->setValue(null, 1);
    }

    /**
     * Clear PageResolver cache.
     */
    private function clearPageResolverCache(): void
    {
        $reflection = new ReflectionClass(PageResolver::class);

        $displayUrlProp = $reflection->getProperty('displayUrl');
        $displayUrlProp->setValue(null, null);

        $pageIdProp = $reflection->getProperty('pageId');
        $pageIdProp->setValue(null, null);
    }

    // =========================================================================
    // build() tests
    // =========================================================================

    /**
     * Test build with empty params returns base URL.
     */
    public function testBuildWithEmptyParamsReturnsBaseUrl(): void
    {
        $result = UrlBuilder::build([]);

        $this->assertEquals('https://example.com/sermons/', $result);
    }

    /**
     * Test build with custom params includes them in URL.
     */
    public function testBuildWithCustomParams(): void
    {
        $result = UrlBuilder::build(['preacher' => 5]);

        $this->assertStringContainsString('preacher=5', $result);
    }

    /**
     * Test build merges GET params.
     */
    public function testBuildMergesGetParams(): void
    {
        $_GET = ['series' => 3];

        $result = UrlBuilder::build(['preacher' => 5]);

        $this->assertStringContainsString('preacher=5', $result);
        $this->assertStringContainsString('series=3', $result);
    }

    /**
     * Test build merges POST params.
     */
    public function testBuildMergesPostParams(): void
    {
        $_POST = ['service' => 2];

        $result = UrlBuilder::build(['preacher' => 5]);

        $this->assertStringContainsString('preacher=5', $result);
        $this->assertStringContainsString('service=2', $result);
    }

    /**
     * Test build with clear=true ignores existing params.
     */
    public function testBuildWithClearIgnoresExistingParams(): void
    {
        $_GET = ['series' => 3, 'preacher' => 1];

        $result = UrlBuilder::build(['sermon_id' => 42], true);

        $this->assertStringContainsString('sermon_id=42', $result);
        $this->assertStringNotContainsString('series=3', $result);
        $this->assertStringNotContainsString('preacher=1', $result);
    }

    /**
     * Test build filters non-whitelisted params from GET/POST.
     */
    public function testBuildFiltersNonWhitelistedParams(): void
    {
        $_GET = ['malicious' => 'value', 'preacher' => 5];

        $result = UrlBuilder::build([]);

        $this->assertStringContainsString('preacher=5', $result);
        $this->assertStringNotContainsString('malicious', $result);
    }

    /**
     * Test build allows non-whitelisted params if explicitly passed.
     */
    public function testBuildAllowsExplicitNonWhitelistedParams(): void
    {
        $result = UrlBuilder::build(['custom_param' => 'value']);

        $this->assertStringContainsString('custom_param=value', $result);
    }

    /**
     * Test build URL-encodes parameters.
     */
    public function testBuildUrlEncodesParams(): void
    {
        $result = UrlBuilder::build(['title' => 'Hello World']);

        $this->assertStringContainsString('title=Hello%20World', $result);
    }

    /**
     * Test build includes all whitelisted params from GET.
     *
     * @dataProvider whitelistedParamsProvider
     */
    public function testBuildIncludesWhitelistedParam(string $param): void
    {
        $_GET = [$param => 'testvalue'];

        $result = UrlBuilder::build([]);

        $this->assertStringContainsString($param . '=testvalue', $result);
    }

    /**
     * Data provider for whitelisted params.
     *
     * @return array<string, array{string}>
     */
    public static function whitelistedParamsProvider(): array
    {
        return [
            'preacher' => ['preacher'],
            'title' => ['title'],
            'date' => ['date'],
            'enddate' => ['enddate'],
            'series' => ['series'],
            'service' => ['service'],
            'sortby' => ['sortby'],
            'dir' => ['dir'],
            'book' => ['book'],
            'stag' => ['stag'],
            'podcast' => ['podcast'],
        ];
    }

    /**
     * Test build starts with base URL.
     */
    public function testBuildStartsWithBaseUrl(): void
    {
        $result = UrlBuilder::build(['preacher' => 1]);

        $this->assertStringStartsWith('https://example.com/sermons/', $result);
    }

    // =========================================================================
    // podcastUrl() tests
    // =========================================================================

    /**
     * Test podcastUrl returns URL with podcast params.
     */
    public function testPodcastUrlReturnsUrlWithPodcastParams(): void
    {
        $result = UrlBuilder::podcastUrl();

        $this->assertStringContainsString('podcast=1', $result);
        $this->assertStringContainsString('dir=desc', $result);
        $this->assertStringContainsString('sortby=m.datetime', $result);
    }

    /**
     * Test podcastUrl replaces spaces with %20.
     */
    public function testPodcastUrlEncodesSpaces(): void
    {
        // Set a URL with space
        $this->setPageResolverCache('https://example.com/my sermons/', '?');

        $result = UrlBuilder::podcastUrl();

        $this->assertStringNotContainsString(' ', $result);
    }

    // =========================================================================
    // sermonLink() tests
    // =========================================================================

    /**
     * Test sermonLink returns URL with sermon_id.
     */
    public function testSermonLinkReturnsUrlWithSermonId(): void
    {
        $sermon = (object) ['id' => 123];
        $result = UrlBuilder::sermonLink($sermon);

        $this->assertStringContainsString('sermon_id=123', $result);
    }

    /**
     * Test sermonLink clears other params (uses clear=true).
     */
    public function testSermonLinkClearsOtherParams(): void
    {
        $_GET = ['preacher' => 5, 'series' => 3];

        $sermon = (object) ['id' => 123];
        $result = UrlBuilder::sermonLink($sermon);

        $this->assertStringContainsString('sermon_id=123', $result);
        $this->assertStringNotContainsString('preacher=5', $result);
        $this->assertStringNotContainsString('series=3', $result);
    }

    // =========================================================================
    // preacherLink() tests
    // =========================================================================

    /**
     * Test preacherLink returns URL with preacher ID.
     */
    public function testPreacherLinkReturnsUrlWithPreacherId(): void
    {
        $sermon = (object) ['pid' => 7];
        $result = UrlBuilder::preacherLink($sermon);

        $this->assertStringContainsString('preacher=7', $result);
    }

    /**
     * Test preacherLink preserves other whitelisted params (uses clear=false).
     */
    public function testPreacherLinkPreservesOtherParams(): void
    {
        $_GET = ['series' => 3];

        $sermon = (object) ['pid' => 7];
        $result = UrlBuilder::preacherLink($sermon);

        $this->assertStringContainsString('preacher=7', $result);
        $this->assertStringContainsString('series=3', $result);
    }

    // =========================================================================
    // seriesLink() tests
    // =========================================================================

    /**
     * Test seriesLink returns URL with series ID.
     */
    public function testSeriesLinkReturnsUrlWithSeriesId(): void
    {
        $sermon = (object) ['ssid' => 15];
        $result = UrlBuilder::seriesLink($sermon);

        $this->assertStringContainsString('series=15', $result);
    }

    // =========================================================================
    // serviceLink() tests
    // =========================================================================

    /**
     * Test serviceLink returns URL with service ID.
     */
    public function testServiceLinkReturnsUrlWithServiceId(): void
    {
        $sermon = (object) ['sid' => 4];
        $result = UrlBuilder::serviceLink($sermon);

        $this->assertStringContainsString('service=4', $result);
    }

    // =========================================================================
    // bookLink() tests
    // =========================================================================

    /**
     * Test bookLink returns URL with book name.
     */
    public function testBookLinkReturnsUrlWithBookName(): void
    {
        $result = UrlBuilder::bookLink('Genesis');

        $this->assertStringContainsString('book=Genesis', $result);
    }

    /**
     * Test bookLink encodes book names with spaces.
     */
    public function testBookLinkEncodesBookNamesWithSpaces(): void
    {
        $result = UrlBuilder::bookLink('1 Corinthians');

        $this->assertStringContainsString('book=1%20Corinthians', $result);
    }

    // =========================================================================
    // tagLink() tests
    // =========================================================================

    /**
     * Test tagLink returns URL with tag.
     */
    public function testTagLinkReturnsUrlWithTag(): void
    {
        $result = UrlBuilder::tagLink('faith');

        $this->assertStringContainsString('stag=faith', $result);
    }

    /**
     * Test tagLink encodes special characters.
     */
    public function testTagLinkEncodesSpecialCharacters(): void
    {
        $result = UrlBuilder::tagLink('faith & hope');

        $this->assertStringContainsString('stag=faith%20%26%20hope', $result);
    }
}
