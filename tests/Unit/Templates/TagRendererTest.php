<?php

/**
 * Tests for TagRenderer.
 *
 * @package SermonBrowser\Tests\Unit\Templates
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Templates;

use Mockery;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Frontend\BibleText;
use SermonBrowser\Frontend\FileDisplay;
use SermonBrowser\Frontend\FilterRenderer;
use SermonBrowser\Frontend\Pagination;
use SermonBrowser\Frontend\TemplateHelper;
use SermonBrowser\Frontend\UrlBuilder;
use SermonBrowser\Frontend\Widgets\PopularWidget;
use SermonBrowser\Templates\TagRenderer;
use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;
use stdClass;

/**
 * Test TagRenderer functionality.
 *
 * Tests the template tag rendering methods for both search and single contexts.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TagRendererTest extends TestCase
{
    /**
     * The renderer instance.
     *
     * @var TagRenderer
     */
    private TagRenderer $renderer;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new TagRenderer();
    }

    // =========================================================================
    // Constructor and Basic Tests
    // =========================================================================

    /**
     * Test TagRenderer can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TagRenderer::class, $this->renderer);
    }

    /**
     * Test getAvailableTags returns array of tag names.
     */
    public function testGetAvailableTagsReturnsArrayOfTagNames(): void
    {
        $tags = $this->renderer->getAvailableTags();

        $this->assertIsArray($tags);
        $this->assertContains('sermon_title', $tags);
        $this->assertContains('preacher_link', $tags);
        $this->assertContains('date', $tags);
    }

    // =========================================================================
    // Sermon Title Tag Tests
    // =========================================================================

    /**
     * Test renderSermonTitle in search context returns link.
     */
    public function testRenderSermonTitleInSearchContextReturnsLink(): void
    {
        $sermon = $this->createMockSermon();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->once()
            ->with(['sermon_id' => 1], true)
            ->andReturn('http://example.com/?sermon_id=1');

        $renderer = new TagRenderer();
        $result = $renderer->renderSermonTitle($sermon, 'search');

        $this->assertStringContainsString('Test Sermon', $result);
        $this->assertStringContainsString('<a href=', $result);
        $this->assertStringContainsString('http://example.com/?sermon_id=1', $result);
    }

    /**
     * Test renderSermonTitle in single context returns plain title.
     */
    public function testRenderSermonTitleInSingleContextReturnsPlainTitle(): void
    {
        $sermon = $this->createMockSermon();

        $result = $this->renderer->renderSermonTitle($sermon, 'single');

        $this->assertEquals('Test Sermon', $result);
        $this->assertStringNotContainsString('<a href=', $result);
    }

    /**
     * Test renderSermonTitle escapes HTML entities.
     */
    public function testRenderSermonTitleEscapesHtml(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->title = '<script>alert("xss")</script>';

        $result = $this->renderer->renderSermonTitle($sermon, 'single');

        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test renderSermonTitle handles slashes correctly.
     */
    public function testRenderSermonTitleHandlesSlashes(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->title = 'Sermon with \\slashes\\';

        $result = $this->renderer->renderSermonTitle($sermon, 'single');

        $this->assertEquals('Sermon with slashes', $result);
    }

    // =========================================================================
    // Sermon Description Tag Tests
    // =========================================================================

    /**
     * Test renderSermonDescription returns formatted description.
     */
    public function testRenderSermonDescriptionReturnsFormattedDescription(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->description = "Line one\n\nLine two";

        // wp_kses_post is stubbed in TestCase::defineCommonWordPressFunctions()
        Functions\expect('wpautop')
            ->once()
            ->andReturnUsing(function ($text) {
                return '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
            });

        $result = $this->renderer->renderSermonDescription($sermon, 'single');

        $this->assertStringContainsString('<p>', $result);
    }

    /**
     * Test renderSermonDescription calls wp_kses_post for XSS protection.
     *
     * This test verifies the code path calls wp_kses_post() before wpautop().
     * The actual XSS filtering is WordPress's responsibility.
     */
    public function testRenderSermonDescriptionCallsWpKsesPost(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->description = 'Test description';

        // Track that wp_kses_post was called with the description
        $wpKsesPostCalled = false;
        $wpKsesPostInput = '';

        Functions\when('wp_kses_post')->alias(function ($text) use (&$wpKsesPostCalled, &$wpKsesPostInput) {
            $wpKsesPostCalled = true;
            $wpKsesPostInput = $text;
            return $text;
        });

        Functions\expect('wpautop')
            ->once()
            ->andReturnUsing(fn($text) => '<p>' . $text . '</p>');

        $this->renderer->renderSermonDescription($sermon, 'single');

        $this->assertTrue($wpKsesPostCalled, 'wp_kses_post() should be called for XSS protection');
        $this->assertEquals('Test description', $wpKsesPostInput);
    }

    /**
     * Test renderSermonDescription preserves safe HTML through wp_kses_post.
     */
    public function testRenderSermonDescriptionPreservesSafeHtml(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->description = '<strong>Bold</strong> and <em>italic</em> text';

        // wp_kses_post is stubbed to pass through (which preserves safe HTML)
        Functions\expect('wpautop')
            ->once()
            ->andReturnUsing(fn($text) => '<p>' . $text . '</p>');

        $result = $this->renderer->renderSermonDescription($sermon, 'single');

        $this->assertStringContainsString('<strong>Bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    // =========================================================================
    // Preacher Tag Tests
    // =========================================================================

    /**
     * Test renderPreacherLink returns link with preacher name.
     */
    public function testRenderPreacherLinkReturnsLinkWithPreacherName(): void
    {
        $sermon = $this->createMockSermon();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->once()
            ->with(['preacher' => 5], false)
            ->andReturn('http://example.com/?preacher=5');

        $renderer = new TagRenderer();
        $result = $renderer->renderPreacherLink($sermon, 'search');

        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('<a href=', $result);
    }

    /**
     * Test renderPreacherDescription returns description div.
     */
    public function testRenderPreacherDescriptionReturnsDescriptionDiv(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->preacher_description = 'Senior Pastor';

        $result = $this->renderer->renderPreacherDescription($sermon, 'single');

        $this->assertStringContainsString('Senior Pastor', $result);
        $this->assertStringContainsString('class="preacher-description"', $result);
    }

    /**
     * Test renderPreacherDescription returns empty when no description.
     */
    public function testRenderPreacherDescriptionReturnsEmptyWhenNoDescription(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->preacher_description = '';

        $result = $this->renderer->renderPreacherDescription($sermon, 'single');

        $this->assertEquals('', $result);
    }

    /**
     * Test renderPreacherImage returns image tag.
     */
    public function testRenderPreacherImageReturnsImageTag(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->image = 'pastor.jpg';

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->once()
            ->with('upload_dir')
            ->andReturn('wp-content/uploads/sermon-browser/');

        Functions\expect('site_url')
            ->once()
            ->andReturn('http://example.com');

        Functions\expect('trailingslashit')
            ->once()
            ->andReturnUsing(fn($url) => rtrim($url, '/') . '/');

        $renderer = new TagRenderer();
        $result = $renderer->renderPreacherImage($sermon, 'single');

        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('pastor.jpg', $result);
        $this->assertStringContainsString('class="preacher"', $result);
    }

    /**
     * Test renderPreacherImage returns empty when no image.
     */
    public function testRenderPreacherImageReturnsEmptyWhenNoImage(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->image = '';

        $result = $this->renderer->renderPreacherImage($sermon, 'single');

        $this->assertEquals('', $result);
    }

    // =========================================================================
    // Series and Service Link Tests
    // =========================================================================

    /**
     * Test renderSeriesLink returns link with series name.
     */
    public function testRenderSeriesLinkReturnsLinkWithSeriesName(): void
    {
        $sermon = $this->createMockSermon();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->once()
            ->with(['series' => 10], false)
            ->andReturn('http://example.com/?series=10');

        $renderer = new TagRenderer();
        $result = $renderer->renderSeriesLink($sermon, 'search');

        $this->assertStringContainsString('Test Series', $result);
        $this->assertStringContainsString('<a href=', $result);
    }

    /**
     * Test renderServiceLink returns link with service name.
     */
    public function testRenderServiceLinkReturnsLinkWithServiceName(): void
    {
        $sermon = $this->createMockSermon();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->once()
            ->with(['service' => 20], false)
            ->andReturn('http://example.com/?service=20');

        $renderer = new TagRenderer();
        $result = $renderer->renderServiceLink($sermon, 'search');

        $this->assertStringContainsString('Sunday Morning', $result);
        $this->assertStringContainsString('<a href=', $result);
    }

    // =========================================================================
    // Date Tag Tests
    // =========================================================================

    /**
     * Test renderDate returns formatted date.
     */
    public function testRenderDateReturnsFormattedDate(): void
    {
        $sermon = $this->createMockSermon();

        $templateHelper = Mockery::mock('alias:' . TemplateHelper::class);
        $templateHelper->shouldReceive('formattedDate')
            ->once()
            ->with($sermon)
            ->andReturn('January 1, 2024');

        $renderer = new TagRenderer();
        $result = $renderer->renderDate($sermon, 'search');

        $this->assertEquals('January 1, 2024', $result);
    }

    // =========================================================================
    // Passage Tag Tests
    // =========================================================================

    /**
     * Test renderFirstPassage returns formatted passage.
     */
    public function testRenderFirstPassageReturnsFormattedPassage(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->start = serialize([['book' => 'John', 'chapter' => '3', 'verse' => '16']]);
        $sermon->end = serialize([['book' => 'John', 'chapter' => '3', 'verse' => '21']]);

        $bibleText = Mockery::mock('alias:' . BibleText::class);
        $bibleText->shouldReceive('getBooks')
            ->once()
            ->andReturn('John 3:16-21');

        $renderer = new TagRenderer();
        $result = $renderer->renderFirstPassage($sermon, 'search');

        $this->assertEquals('John 3:16-21', $result);
    }

    /**
     * Test renderFirstPassage returns empty when no passage.
     */
    public function testRenderFirstPassageReturnsEmptyWhenNoPassage(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->start = serialize([]);
        $sermon->end = serialize([]);

        $result = $this->renderer->renderFirstPassage($sermon, 'search');

        $this->assertEquals('', $result);
    }

    // =========================================================================
    // Pagination Tag Tests
    // =========================================================================

    /**
     * Test renderNextPage calls Pagination::printNextPageLink.
     */
    public function testRenderNextPageReturnsNextPageLink(): void
    {
        $pagination = Mockery::mock('alias:' . Pagination::class);
        $pagination->shouldReceive('printNextPageLink')
            ->once();

        $renderer = new TagRenderer();
        $renderer->renderNextPage(null, 'search');
        $this->assertTrue(true);
    }

    /**
     * Test renderPreviousPage calls Pagination::printPrevPageLink.
     */
    public function testRenderPreviousPageReturnsPreviousPageLink(): void
    {
        $pagination = Mockery::mock('alias:' . Pagination::class);
        $pagination->shouldReceive('printPrevPageLink')
            ->once();

        $renderer = new TagRenderer();
        $renderer->renderPreviousPage(null, 'search');
        $this->assertTrue(true);
    }

    // =========================================================================
    // Podcast Tag Tests
    // =========================================================================

    /**
     * Test renderPodcast returns podcast URL.
     */
    public function testRenderPodcastReturnsPodcastUrl(): void
    {
        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->once()
            ->with('podcast_url')
            ->andReturn('http://example.com/podcast.xml');

        $renderer = new TagRenderer();
        $result = $renderer->renderPodcast(null, 'search');

        $this->assertEquals('http://example.com/podcast.xml', $result);
    }

    /**
     * Test renderPodcastForSearch returns custom podcast URL.
     */
    public function testRenderPodcastForSearchReturnsCustomPodcastUrl(): void
    {
        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('podcastUrl')
            ->once()
            ->andReturn('http://example.com/custom-podcast.xml');

        $renderer = new TagRenderer();
        $result = $renderer->renderPodcastForSearch(null, 'search');

        $this->assertEquals('http://example.com/custom-podcast.xml', $result);
    }

    /**
     * Test renderItunesPodcast returns itpc URL.
     */
    public function testRenderItunesPodcastReturnsItpcUrl(): void
    {
        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->once()
            ->with('podcast_url')
            ->andReturn('http://example.com/podcast.xml');

        $renderer = new TagRenderer();
        $result = $renderer->renderItunesPodcast(null, 'search');

        $this->assertEquals('itpc://example.com/podcast.xml', $result);
    }

    /**
     * Test renderItunesPodcastForSearch returns itpc custom URL.
     */
    public function testRenderItunesPodcastForSearchReturnsItpcCustomUrl(): void
    {
        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('podcastUrl')
            ->once()
            ->andReturn('http://example.com/custom-podcast.xml');

        $renderer = new TagRenderer();
        $result = $renderer->renderItunesPodcastForSearch(null, 'search');

        $this->assertEquals('itpc://example.com/custom-podcast.xml', $result);
    }

    // =========================================================================
    // Podcast Icon Tag Tests
    // =========================================================================

    /**
     * Test renderPodcastIcon returns icon image tag.
     */
    public function testRenderPodcastIconReturnsIconImageTag(): void
    {
        $result = $this->renderer->renderPodcastIcon(null, 'search');

        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('podcast.png', $result);
        $this->assertStringContainsString('class="podcasticon"', $result);
    }

    /**
     * Test renderPodcastIconForSearch returns custom icon image tag.
     */
    public function testRenderPodcastIconForSearchReturnsCustomIconImageTag(): void
    {
        $result = $this->renderer->renderPodcastIconForSearch(null, 'search');

        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('podcast_custom.png', $result);
    }

    // =========================================================================
    // Credit and Edit Link Tests
    // =========================================================================

    /**
     * Test renderCreditLink returns credit div.
     */
    public function testRenderCreditLinkReturnsCreditDiv(): void
    {
        $result = $this->renderer->renderCreditLink(null, 'search');

        $this->assertStringContainsString('id="poweredbysermonbrowser"', $result);
        $this->assertStringContainsString('Powered by Sermon Browser', $result);
    }

    /**
     * Test renderEditLink calls TemplateHelper::editLink.
     */
    public function testRenderEditLinkCallsTemplateHelperEditLink(): void
    {
        $sermon = $this->createMockSermon();

        $templateHelper = Mockery::mock('alias:' . TemplateHelper::class);
        $templateHelper->shouldReceive('editLink')
            ->once()
            ->with(1);

        $renderer = new TagRenderer();
        $renderer->renderEditLink($sermon, 'search');
        $this->assertTrue(true);
    }

    // =========================================================================
    // Tags Tests
    // =========================================================================

    /**
     * Test renderTags returns formatted tags.
     */
    public function testRenderTagsReturnsFormattedTags(): void
    {
        $tags = ['faith', 'hope', 'love'];

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->times(3)
            ->andReturnUsing(fn($arr) => 'http://example.com/?stag=' . $arr['stag']);

        $renderer = new TagRenderer();
        $result = $renderer->renderTags($tags, 'single');

        $this->assertStringContainsString('faith', $result);
        $this->assertStringContainsString('hope', $result);
        $this->assertStringContainsString('love', $result);
        $this->assertStringContainsString('<a href=', $result);
    }

    // =========================================================================
    // Navigation Link Tests
    // =========================================================================

    /**
     * Test renderNextSermon calls TemplateHelper::printNextSermonLink.
     */
    public function testRenderNextSermonReturnsNextSermonLink(): void
    {
        $sermon = $this->createMockSermon();

        $templateHelper = Mockery::mock('alias:' . TemplateHelper::class);
        $templateHelper->shouldReceive('printNextSermonLink')
            ->once()
            ->with($sermon);

        $renderer = new TagRenderer();
        $renderer->renderNextSermon($sermon, 'single');
        $this->assertTrue(true);
    }

    /**
     * Test renderPrevSermon calls TemplateHelper::printPrevSermonLink.
     */
    public function testRenderPrevSermonReturnsPreviousSermonLink(): void
    {
        $sermon = $this->createMockSermon();

        $templateHelper = Mockery::mock('alias:' . TemplateHelper::class);
        $templateHelper->shouldReceive('printPrevSermonLink')
            ->once()
            ->with($sermon);

        $renderer = new TagRenderer();
        $renderer->renderPrevSermon($sermon, 'single');
        $this->assertTrue(true);
    }

    /**
     * Test renderSamedaySermon calls TemplateHelper::printSamedaySermonLink.
     */
    public function testRenderSamedaySermonReturnsSameDaySermonLink(): void
    {
        $sermon = $this->createMockSermon();

        $templateHelper = Mockery::mock('alias:' . TemplateHelper::class);
        $templateHelper->shouldReceive('printSamedaySermonLink')
            ->once()
            ->with($sermon);

        $renderer = new TagRenderer();
        $renderer->renderSamedaySermon($sermon, 'single');
        $this->assertTrue(true);
    }

    // =========================================================================
    // Bible Translation Tests
    // =========================================================================

    /**
     * Test renderBibleText returns bible text for given translation.
     */
    public function testRenderBibleTextReturnsBibleTextForEsv(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->start = [['book' => 'John', 'chapter' => '3', 'verse' => '16']];
        $sermon->end = [['book' => 'John', 'chapter' => '3', 'verse' => '21']];

        $bibleText = Mockery::mock('alias:' . BibleText::class);
        $bibleText->shouldReceive('addBibleText')
            ->once()
            ->andReturn('<div class="esv">John 3:16-21 text...</div>');

        $renderer = new TagRenderer();
        $result = $renderer->renderBibleText($sermon, 'esv');

        $this->assertStringContainsString('esv', $result);
    }

    /**
     * Test renderEsvText calls BibleText::addBibleText with esv.
     */
    public function testRenderEsvTextCallsRenderBibleTextWithEsv(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->start = [['book' => 'John', 'chapter' => '3', 'verse' => '16']];
        $sermon->end = [['book' => 'John', 'chapter' => '3', 'verse' => '21']];

        $bibleText = Mockery::mock('alias:' . BibleText::class);
        $bibleText->shouldReceive('addBibleText')
            ->once()
            ->with(
                ['book' => 'John', 'chapter' => '3', 'verse' => '16'],
                ['book' => 'John', 'chapter' => '3', 'verse' => '21'],
                'esv'
            )
            ->andReturn('<div class="esv">...</div>');

        $renderer = new TagRenderer();
        $result = $renderer->renderEsvText($sermon, 'single');

        $this->assertStringContainsString('esv', $result);
    }

    // =========================================================================
    // Loop Marker Tests (for TagParser coordination)
    // =========================================================================

    /**
     * Test loop markers return marker strings.
     */
    public function testLoopMarkersReturnMarkerStrings(): void
    {
        $this->assertEquals('{{SERMONS_LOOP_START}}', $this->renderer->renderSermonsLoopStart(null, 'search'));
        $this->assertEquals('{{SERMONS_LOOP_END}}', $this->renderer->renderSermonsLoopEnd(null, 'search'));
        $this->assertEquals('{{FILES_LOOP_START}}', $this->renderer->renderFilesLoopStart(null, 'search'));
        $this->assertEquals('{{FILES_LOOP_END}}', $this->renderer->renderFilesLoopEnd(null, 'search'));
        $this->assertEquals('{{EMBED_LOOP_START}}', $this->renderer->renderEmbedLoopStart(null, 'search'));
        $this->assertEquals('{{EMBED_LOOP_END}}', $this->renderer->renderEmbedLoopEnd(null, 'search'));
        $this->assertEquals('{{PASSAGES_LOOP_START}}', $this->renderer->renderPassagesLoopStart(null, 'single'));
        $this->assertEquals('{{PASSAGES_LOOP_END}}', $this->renderer->renderPassagesLoopEnd(null, 'single'));
    }

    // =========================================================================
    // Filter Form and Other Complex Tags
    // =========================================================================

    /**
     * Test renderFiltersForm calls FilterRenderer::render.
     */
    public function testRenderFiltersFormCallsFilterRendererRender(): void
    {
        $atts = ['filter' => 'dropdown', 'filterhide' => ''];

        $filterRenderer = Mockery::mock('alias:' . FilterRenderer::class);
        $filterRenderer->shouldReceive('render')
            ->once()
            ->with($atts);

        $renderer = new TagRenderer();
        $renderer->renderFiltersForm($atts, 'search');
        $this->assertTrue(true);
    }

    /**
     * Test renderMostPopular calls PopularWidget::printMostPopular.
     */
    public function testRenderMostPopularCallsPopularWidgetPrintMostPopular(): void
    {
        $popularWidget = Mockery::mock('alias:' . PopularWidget::class);
        $popularWidget->shouldReceive('printMostPopular')
            ->once();

        $renderer = new TagRenderer();
        $renderer->renderMostPopular(null, 'search');
        $this->assertTrue(true);
    }

    /**
     * Test renderTagCloud calls TemplateHelper::printTagClouds.
     */
    public function testRenderTagCloudCallsTemplateHelperPrintTagClouds(): void
    {
        $templateHelper = Mockery::mock('alias:' . TemplateHelper::class);
        $templateHelper->shouldReceive('printTagClouds')
            ->once();

        $renderer = new TagRenderer();
        $renderer->renderTagCloud(null, 'search');
        $this->assertTrue(true);
    }

    /**
     * Test renderSermonsCount returns count.
     */
    public function testRenderSermonsCountReturnsCount(): void
    {
        $result = $this->renderer->renderSermonsCount(42, 'search');

        $this->assertEquals('42', $result);
    }

    // =========================================================================
    // File and Embed Rendering Tests
    // =========================================================================

    /**
     * Test renderFile calls FileDisplay::printUrl.
     */
    public function testRenderFileReturnsFileUrl(): void
    {
        $mediaName = 'sermon.mp3';

        $fileDisplay = Mockery::mock('alias:' . FileDisplay::class);
        $fileDisplay->shouldReceive('printUrl')
            ->once()
            ->with('sermon.mp3');

        $renderer = new TagRenderer();
        $renderer->renderFile($mediaName, 'search');
        $this->assertTrue(true);
    }

    /**
     * Test renderFileWithDownload calls FileDisplay::printUrlLink.
     */
    public function testRenderFileWithDownloadReturnsFileWithDownloadLink(): void
    {
        $mediaName = 'sermon.mp3';

        $fileDisplay = Mockery::mock('alias:' . FileDisplay::class);
        $fileDisplay->shouldReceive('printUrlLink')
            ->once()
            ->with('sermon.mp3');

        $renderer = new TagRenderer();
        $renderer->renderFileWithDownload($mediaName, 'search');
        $this->assertTrue(true);
    }

    /**
     * Test renderEmbed returns embed code.
     */
    public function testRenderEmbedReturnsEmbedCode(): void
    {
        $mediaName = base64_encode('<iframe src="https://youtube.com/embed/123"></iframe>');

        Functions\expect('do_shortcode')
            ->once()
            ->andReturnUsing(fn($code) => $code);

        $result = $this->renderer->renderEmbed($mediaName, 'search');

        $this->assertStringContainsString('iframe', $result);
    }

    // =========================================================================
    // Passage Rendering Tests
    // =========================================================================

    /**
     * Test renderPassage returns passage reference.
     */
    public function testRenderPassageReturnsPassageReference(): void
    {
        $start = ['book' => 'John', 'chapter' => '3', 'verse' => '16'];
        $end = ['book' => 'John', 'chapter' => '3', 'verse' => '21'];

        $bibleText = Mockery::mock('alias:' . BibleText::class);
        $bibleText->shouldReceive('getBooks')
            ->once()
            ->with($start, $end)
            ->andReturn('John 3:16-21');

        $renderer = new TagRenderer();
        $result = $renderer->renderPassage($start, $end);

        $this->assertEquals('John 3:16-21', $result);
    }

    /**
     * Test renderBiblePassage calls BibleText::printBiblePassage.
     */
    public function testRenderBiblePassageCallsBibleTextPrintBiblePassage(): void
    {
        $sermon = $this->createMockSermon();
        $sermon->start = [['book' => 'John', 'chapter' => '3', 'verse' => '16']];
        $sermon->end = [['book' => 'John', 'chapter' => '3', 'verse' => '21']];

        $bibleText = Mockery::mock('alias:' . BibleText::class);
        $bibleText->shouldReceive('printBiblePassage')
            ->once();

        $renderer = new TagRenderer();
        $renderer->renderBiblePassage($sermon, 'single');
        $this->assertTrue(true);
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
        $sermon->start = serialize([]);
        $sermon->end = serialize([]);

        return $sermon;
    }
}
