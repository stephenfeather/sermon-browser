<?php
/**
 * Tests for TagParser.
 *
 * @package SermonBrowser\Tests\Unit\Templates
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Templates;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Templates\TagParser;
use SermonBrowser\Templates\TagRenderer;
use Brain\Monkey\Functions;
use stdClass;
use Mockery;

/**
 * Test TagParser functionality.
 *
 * Tests the regex-based template parsing and loop handling.
 */
class TagParserTest extends TestCase
{
    /**
     * The parser instance.
     *
     * @var TagParser
     */
    private TagParser $parser;

    /**
     * Mock TagRenderer.
     *
     * @var TagRenderer|\Mockery\MockInterface
     */
    private $mockRenderer;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRenderer = Mockery::mock(TagRenderer::class);
        $this->parser = new TagParser($this->mockRenderer);
    }

    // =========================================================================
    // Constructor and Basic Tests
    // =========================================================================

    /**
     * Test TagParser can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TagParser::class, $this->parser);
    }

    /**
     * Test TagParser can be instantiated with default renderer.
     */
    public function testCanBeInstantiatedWithDefaultRenderer(): void
    {
        $parser = new TagParser();
        $this->assertInstanceOf(TagParser::class, $parser);
    }

    // =========================================================================
    // Simple Tag Replacement Tests
    // =========================================================================

    /**
     * Test parse replaces simple tags.
     */
    public function testParseReplacesSimpleTags(): void
    {
        $template = '<h1>[sermon_title]</h1>';
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon, 'single')
            ->andReturn('My Sermon Title');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<h1>My Sermon Title</h1>', $result);
    }

    /**
     * Test parse replaces multiple tags.
     */
    public function testParseReplacesMultipleTags(): void
    {
        $template = '<div>[sermon_title] by [preacher_link]</div>';
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title', 'preacher_link']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon, 'single')
            ->andReturn('My Sermon');

        $this->mockRenderer
            ->shouldReceive('renderPreacherLink')
            ->with($sermon, 'single')
            ->andReturn('<a href="#">John Doe</a>');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div>My Sermon by <a href="#">John Doe</a></div>', $result);
    }

    /**
     * Test parse preserves unknown tags.
     */
    public function testParsePreservesUnknownTags(): void
    {
        $template = '<div>[sermon_title] [unknown_tag]</div>';
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon, 'single')
            ->andReturn('My Sermon');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div>My Sermon [unknown_tag]</div>', $result);
    }

    /**
     * Test parse returns template unchanged when no tags.
     */
    public function testParseReturnsTemplateUnchangedWhenNoTags(): void
    {
        $template = '<div>Static content only</div>';
        $data = [];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div>Static content only</div>', $result);
    }

    // =========================================================================
    // Context-Specific Tag Tests
    // =========================================================================

    /**
     * Test parse uses sermons_count from data in search context.
     */
    public function testParseUsesSermonsCountFromData(): void
    {
        $template = '<span>Total: [sermons_count]</span>';
        $data = ['record_count' => 42];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermons_count']);

        $this->mockRenderer
            ->shouldReceive('renderSermonsCount')
            ->with(42, 'search')
            ->andReturn('42');

        $result = $this->parser->parse($template, $data, 'search');

        $this->assertEquals('<span>Total: 42</span>', $result);
    }

    /**
     * Test parse uses atts for filters_form.
     */
    public function testParseUsesAttsForFiltersForm(): void
    {
        $template = '<div>[filters_form]</div>';
        $atts = ['filter' => 'dropdown'];
        $data = ['atts' => $atts];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['filters_form']);

        $this->mockRenderer
            ->shouldReceive('renderFiltersForm')
            ->with($atts, 'search')
            ->andReturn('<form>Filters</form>');

        $result = $this->parser->parse($template, $data, 'search');

        $this->assertEquals('<div><form>Filters</form></div>', $result);
    }

    /**
     * Test parse uses tags from data for tags tag.
     */
    public function testParseUsesTagsFromData(): void
    {
        $template = '<div>Tags: [tags]</div>';
        $tags = ['faith', 'hope'];
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon, 'Tags' => $tags];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['tags']);

        $this->mockRenderer
            ->shouldReceive('renderTags')
            ->with($tags, 'single')
            ->andReturn('faith, hope');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div>Tags: faith, hope</div>', $result);
    }

    // =========================================================================
    // Sermons Loop Tests
    // =========================================================================

    /**
     * Test parse handles sermons loop markers.
     */
    public function testParseHandlesSermonsLoop(): void
    {
        $template = '<ul>[sermons_loop]<li>[sermon_title]</li>[/sermons_loop]</ul>';
        $sermon1 = $this->createMockSermon();
        $sermon1->title = 'Sermon One';
        $sermon2 = $this->createMockSermon();
        $sermon2->title = 'Sermon Two';
        $data = ['sermons' => [$sermon1, $sermon2]];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon1, 'search')
            ->andReturn('Sermon One');

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon2, 'search')
            ->andReturn('Sermon Two');

        $result = $this->parser->parse($template, $data, 'search');

        $this->assertEquals('<ul><li>Sermon One</li><li>Sermon Two</li></ul>', $result);
    }

    /**
     * Test parse handles empty sermons array.
     */
    public function testParseHandlesEmptySermonsLoop(): void
    {
        $template = '<ul>[sermons_loop]<li>[sermon_title]</li>[/sermons_loop]</ul>';
        $data = ['sermons' => []];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        $result = $this->parser->parse($template, $data, 'search');

        $this->assertEquals('<ul></ul>', $result);
    }

    // =========================================================================
    // Files Loop Tests
    // =========================================================================

    /**
     * Test parse handles files loop.
     */
    public function testParseHandlesFilesLoop(): void
    {
        $template = '<div>[files_loop][file][/files_loop]</div>';
        $sermon = $this->createMockSermon();
        $media = [
            (object) ['name' => 'sermon.mp3', 'type' => (object) ['type' => 'MP3']],
            (object) ['name' => 'notes.pdf', 'type' => (object) ['type' => 'PDF']],
        ];
        $data = ['Sermon' => $sermon, 'media' => $media];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['file']);

        $this->mockRenderer
            ->shouldReceive('renderFile')
            ->with('sermon.mp3', 'single')
            ->andReturn('<audio>sermon.mp3</audio>');

        $this->mockRenderer
            ->shouldReceive('renderFile')
            ->with('notes.pdf', 'single')
            ->andReturn('<a href="notes.pdf">PDF</a>');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div><audio>sermon.mp3</audio><a href="notes.pdf">PDF</a></div>', $result);
    }

    /**
     * Test parse handles files loop with no files.
     */
    public function testParseHandlesFilesLoopWithNoFiles(): void
    {
        $template = '<div>[files_loop][file][/files_loop]</div>';
        $data = ['media' => []];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['file']);

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div></div>', $result);
    }

    // =========================================================================
    // Embed Loop Tests
    // =========================================================================

    /**
     * Test parse handles embed loop (Code type media).
     */
    public function testParseHandlesEmbedLoop(): void
    {
        $template = '<div>[embed_loop][embed][/embed_loop]</div>';
        $encodedEmbed = base64_encode('<iframe src="youtube.com"></iframe>');
        $media = [
            (object) ['name' => $encodedEmbed, 'type' => (object) ['type' => 'Code']],
        ];
        $data = ['media' => $media];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['embed']);

        $this->mockRenderer
            ->shouldReceive('renderEmbed')
            ->with($encodedEmbed, 'single')
            ->andReturn('<iframe src="youtube.com"></iframe>');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div><iframe src="youtube.com"></iframe></div>', $result);
    }

    // =========================================================================
    // Passages Loop Tests
    // =========================================================================

    /**
     * Test parse handles passages loop.
     */
    public function testParseHandlesPassagesLoop(): void
    {
        $template = '<div>[passages_loop][passage][/passages_loop]</div>';
        $sermon = $this->createMockSermon();
        $sermon->start = [
            ['book' => 'John', 'chapter' => '3', 'verse' => '16'],
            ['book' => 'Romans', 'chapter' => '8', 'verse' => '28'],
        ];
        $sermon->end = [
            ['book' => 'John', 'chapter' => '3', 'verse' => '21'],
            ['book' => 'Romans', 'chapter' => '8', 'verse' => '30'],
        ];
        $data = ['Sermon' => $sermon];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['passage']);

        $this->mockRenderer
            ->shouldReceive('renderPassage')
            ->with(['book' => 'John', 'chapter' => '3', 'verse' => '16'], ['book' => 'John', 'chapter' => '3', 'verse' => '21'])
            ->andReturn('John 3:16-21');

        $this->mockRenderer
            ->shouldReceive('renderPassage')
            ->with(['book' => 'Romans', 'chapter' => '8', 'verse' => '28'], ['book' => 'Romans', 'chapter' => '8', 'verse' => '30'])
            ->andReturn('Romans 8:28-30');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div>John 3:16-21Romans 8:28-30</div>', $result);
    }

    // =========================================================================
    // Nested Content Tests
    // =========================================================================

    /**
     * Test parse handles nested loops correctly (sermons with files).
     */
    public function testParseHandlesNestedLoops(): void
    {
        $template = '[sermons_loop]<div>[sermon_title][files_loop] [file][/files_loop]</div>[/sermons_loop]';

        $sermon1 = $this->createMockSermon();
        $sermon1->title = 'Sermon One';
        $sermon1->id = 1;

        $sermon2 = $this->createMockSermon();
        $sermon2->title = 'Sermon Two';
        $sermon2->id = 2;

        $data = [
            'sermons' => [$sermon1, $sermon2],
            'media' => [],  // Files would need to be fetched per-sermon
        ];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title', 'file']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon1, 'search')
            ->andReturn('Sermon One');

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon2, 'search')
            ->andReturn('Sermon Two');

        $result = $this->parser->parse($template, $data, 'search');

        $this->assertEquals('<div>Sermon One</div><div>Sermon Two</div>', $result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * Test parse handles null sermon gracefully.
     */
    public function testParseHandlesNullSermon(): void
    {
        $template = '<h1>[sermon_title]</h1>';
        $data = ['Sermon' => null];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with(null, 'single')
            ->andReturn('');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<h1></h1>', $result);
    }

    /**
     * Test parse handles empty template.
     */
    public function testParseHandlesEmptyTemplate(): void
    {
        $result = $this->parser->parse('', [], 'single');

        $this->assertEquals('', $result);
    }

    /**
     * Test parse handles tags at different positions.
     */
    public function testParseHandlesTagsAtDifferentPositions(): void
    {
        $template = '[sermon_title] middle [date] end';
        $sermon = $this->createMockSermon();
        $data = ['Sermon' => $sermon];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title', 'date']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->andReturn('Title');

        $this->mockRenderer
            ->shouldReceive('renderDate')
            ->andReturn('2024-01-01');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('Title middle 2024-01-01 end', $result);
    }

    /**
     * Test parse with file_with_download tag.
     */
    public function testParseHandlesFileWithDownloadTag(): void
    {
        $template = '<div>[files_loop][file_with_download][/files_loop]</div>';
        $media = [
            (object) ['name' => 'sermon.mp3', 'type' => (object) ['type' => 'MP3']],
        ];
        $data = ['media' => $media];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['file_with_download']);

        $this->mockRenderer
            ->shouldReceive('renderFileWithDownload')
            ->with('sermon.mp3', 'single')
            ->andReturn('<a href="sermon.mp3" download>Download</a>');

        $result = $this->parser->parse($template, $data, 'single');

        $this->assertEquals('<div><a href="sermon.mp3" download>Download</a></div>', $result);
    }

    // =========================================================================
    // Search Context with Sermons Array Tests
    // =========================================================================

    /**
     * Test parse in search context uses sermons array for sermon tags.
     */
    public function testParseInSearchContextUsesSermonsArray(): void
    {
        $template = '[sermons_loop]<div class="sermon">[sermon_title] - [date]</div>[/sermons_loop]';

        $sermon1 = $this->createMockSermon();
        $sermon2 = $this->createMockSermon();
        $sermon2->title = 'Second Sermon';

        $data = ['sermons' => [$sermon1, $sermon2]];

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title', 'date']);

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon1, 'search')
            ->andReturn('Test Sermon');

        $this->mockRenderer
            ->shouldReceive('renderSermonTitle')
            ->with($sermon2, 'search')
            ->andReturn('Second Sermon');

        $this->mockRenderer
            ->shouldReceive('renderDate')
            ->with($sermon1, 'search')
            ->andReturn('Jan 1');

        $this->mockRenderer
            ->shouldReceive('renderDate')
            ->with($sermon2, 'search')
            ->andReturn('Jan 2');

        $result = $this->parser->parse($template, $data, 'search');

        $this->assertEquals(
            '<div class="sermon">Test Sermon - Jan 1</div><div class="sermon">Second Sermon - Jan 2</div>',
            $result
        );
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
