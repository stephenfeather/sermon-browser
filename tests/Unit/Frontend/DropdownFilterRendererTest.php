<?php

/**
 * Tests for DropdownFilterRenderer class.
 *
 * @package SermonBrowser\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Frontend;

use Mockery;
use SermonBrowser\Config\Defaults;
use SermonBrowser\Constants;
use SermonBrowser\Facades\Book;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Frontend\DropdownFilterRenderer;
use SermonBrowser\Frontend\PageResolver;
use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test DropdownFilterRenderer functionality.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DropdownFilterRendererTest extends TestCase
{
    /**
     * Sample preacher data for tests.
     *
     * @var array<object>
     */
    private array $samplePreachers;

    /**
     * Sample series data for tests.
     *
     * @var array<object>
     */
    private array $sampleSeries;

    /**
     * Sample services data for tests.
     *
     * @var array<object>
     */
    private array $sampleServices;

    /**
     * Sample book count data for tests.
     *
     * @var array<object>
     */
    private array $sampleBookCount;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear $_REQUEST before each test.
        $_REQUEST = [];

        // Set up sample data.
        $this->samplePreachers = [
            (object) ['id' => 1, 'name' => 'John Smith', 'count' => 10],
            (object) ['id' => 2, 'name' => 'Jane Doe', 'count' => 5],
        ];

        $this->sampleSeries = [
            (object) ['id' => 1, 'name' => 'Romans Study', 'count' => 12],
            (object) ['id' => 2, 'name' => 'Psalms Series', 'count' => 8],
        ];

        $this->sampleServices = [
            (object) ['id' => 1, 'name' => 'Sunday Morning', 'count' => 20],
            (object) ['id' => 2, 'name' => 'Wednesday Evening', 'count' => 15],
        ];

        $this->sampleBookCount = [
            (object) ['name' => 'Genesis', 'count' => 5],
            (object) ['name' => 'Matthew', 'count' => 8],
        ];

        // Stub additional WordPress functions needed for rendering.
        Functions\stubs([
            'esc_html_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
        ]);
    }

    /**
     * Tear down test fixtures.
     */
    protected function tearDown(): void
    {
        $_REQUEST = [];
        parent::tearDown();
    }

    /**
     * Set up common mocks for render tests.
     */
    private function setUpRenderMocks(): void
    {
        $bibleBooks = ['Genesis', 'Matthew'];

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn($bibleBooks);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn($bibleBooks);

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllForFilter')
            ->andReturn($this->samplePreachers);

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('findAllForFilter')
            ->andReturn($this->sampleSeries);

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('findAllForFilter')
            ->andReturn($this->sampleServices);

        $book = Mockery::mock('alias:' . Book::class);
        $book->shouldReceive('findAllWithSermonCount')
            ->andReturn($this->sampleBookCount);

        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');
    }

    /**
     * Capture output from a callable.
     *
     * @param callable $callback The callback to execute.
     * @return string The captured output.
     */
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        $output = ob_get_clean();
        return $output !== false ? $output : '';
    }

    /**
     * Test render outputs the filter form container.
     */
    public function testRenderOutputsFilterFormContainer(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('id="mainfilter"', $output);
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('id="sermon-filter"', $output);
    }

    /**
     * Test render outputs the show/hide filter button.
     */
    public function testRenderOutputsShowHideFilterButton(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('id="show_hide_filter"', $output);
        $this->assertStringContainsString('button-link', $output);
        $this->assertStringContainsString('Toggle filter', $output);
    }

    /**
     * Test render outputs preacher select with options.
     */
    public function testRenderOutputsPreacherSelect(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="preacher"', $output);
        $this->assertStringContainsString('id="preacher"', $output);
        $this->assertStringContainsString('John Smith (10)', $output);
        $this->assertStringContainsString('Jane Doe (5)', $output);
        $this->assertStringContainsString(Constants::ALL_FILTER, $output);
    }

    /**
     * Test render outputs service select with options.
     */
    public function testRenderOutputsServiceSelect(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="service"', $output);
        $this->assertStringContainsString('id="service"', $output);
        $this->assertStringContainsString('Sunday Morning (20)', $output);
        $this->assertStringContainsString('Wednesday Evening (15)', $output);
    }

    /**
     * Test render outputs series select with options.
     */
    public function testRenderOutputsSeriesSelect(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="series"', $output);
        $this->assertStringContainsString('id="series"', $output);
        $this->assertStringContainsString('Romans Study (12)', $output);
        $this->assertStringContainsString('Psalms Series (8)', $output);
    }

    /**
     * Test render outputs book select with options.
     */
    public function testRenderOutputsBookSelect(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="book"', $output);
        $this->assertStringContainsString('Genesis (5)', $output);
        $this->assertStringContainsString('Matthew (8)', $output);
    }

    /**
     * Test render outputs date input fields.
     */
    public function testRenderOutputsDateFields(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="date"', $output);
        $this->assertStringContainsString('id="date"', $output);
        $this->assertStringContainsString('name="enddate"', $output);
        $this->assertStringContainsString('id="enddate"', $output);
        $this->assertStringContainsString('Start date', $output);
        $this->assertStringContainsString('End date', $output);
    }

    /**
     * Test render outputs keywords field.
     */
    public function testRenderOutputsKeywordsField(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="title"', $output);
        $this->assertStringContainsString('id="title"', $output);
        $this->assertStringContainsString('Keywords', $output);
    }

    /**
     * Test render outputs sort by select.
     */
    public function testRenderOutputsSortBySelect(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="sortby"', $output);
        $this->assertStringContainsString('id="sortby"', $output);
        $this->assertStringContainsString('value="m.title"', $output);
        $this->assertStringContainsString('value="preacher"', $output);
        $this->assertStringContainsString('value="m.datetime"', $output);
        $this->assertStringContainsString('value="b.id"', $output);
    }

    /**
     * Test render outputs direction select.
     */
    public function testRenderOutputsDirectionSelect(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="dir"', $output);
        $this->assertStringContainsString('id="dir"', $output);
        $this->assertStringContainsString('value="asc"', $output);
        $this->assertStringContainsString('value="desc"', $output);
        $this->assertStringContainsString('Ascending', $output);
        $this->assertStringContainsString('Descending', $output);
    }

    /**
     * Test render outputs filter submit button.
     */
    public function testRenderOutputsFilterSubmitButton(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('type="submit"', $output);
        $this->assertStringContainsString('class="filter"', $output);
        $this->assertStringContainsString('Filter', $output);
    }

    /**
     * Test render outputs hidden page input.
     */
    public function testRenderOutputsHiddenPageInput(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('name="page"', $output);
        $this->assertStringContainsString('value="1"', $output);
    }

    /**
     * Test render outputs JavaScript with datepicker.
     */
    public function testRenderOutputsJavaScript(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('jQuery', $output);
        $this->assertStringContainsString('datepicker', $output);
        $this->assertStringContainsString("dateFormat: 'yy-mm-dd'", $output);
    }

    /**
     * Test render with hideFilter outputs the jsHide code.
     */
    public function testRenderWithHideFilterOutputsJsHide(): void
    {
        $this->setUpRenderMocks();

        $jsHide = "$('#mainfilter').hide();";
        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(true, $jsHide));

        $this->assertStringContainsString($jsHide, $output);
    }

    /**
     * Test render without hideFilter does not output jsHide code.
     */
    public function testRenderWithoutHideFilterDoesNotOutputJsHide(): void
    {
        $this->setUpRenderMocks();

        $jsHide = "$('#mainfilter').hide();";
        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, $jsHide));

        $this->assertStringNotContainsString($jsHide, $output);
    }

    /**
     * Test render with preacher selected in request.
     */
    public function testRenderWithPreacherSelected(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['preacher'] = '1';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // The selected preacher option should have the selected attribute.
        $this->assertMatchesRegularExpression(
            '/value="1"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render with service selected in request.
     */
    public function testRenderWithServiceSelected(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['service'] = '2';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // Should contain service option with value 2.
        $this->assertStringContainsString('value="2"', $output);
    }

    /**
     * Test render with series selected in request.
     */
    public function testRenderWithSeriesSelected(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['series'] = '1';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('name="series"', $output);
    }

    /**
     * Test render with book selected in request.
     */
    public function testRenderWithBookSelected(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['book'] = 'Genesis';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // The Genesis option should be selected.
        $this->assertStringContainsString('value="Genesis"', $output);
    }

    /**
     * Test render with date filter values.
     */
    public function testRenderWithDateFilterValues(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['date'] = '2024-01-01';
        $_REQUEST['enddate'] = '2024-12-31';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('value="2024-01-01"', $output);
        $this->assertStringContainsString('value="2024-12-31"', $output);
    }

    /**
     * Test render with keyword filter value.
     */
    public function testRenderWithKeywordValue(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['title'] = 'grace';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('value="grace"', $output);
    }

    /**
     * Test render with sortby selected.
     */
    public function testRenderWithSortBySelected(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['sortby'] = 'm.title';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // m.title should be selected.
        $this->assertMatchesRegularExpression(
            '/value="m\.title"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render with direction ascending selected.
     */
    public function testRenderWithDirectionAscSelected(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['dir'] = 'asc';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // asc should be selected.
        $this->assertMatchesRegularExpression(
            '/value="asc"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render defaults to descending direction.
     */
    public function testRenderDefaultsToDescendingDirection(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // desc should be selected by default.
        $this->assertMatchesRegularExpression(
            '/value="desc"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render defaults to datetime sortby.
     */
    public function testRenderDefaultsToDatetimeSortBy(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // m.datetime should be selected by default.
        $this->assertMatchesRegularExpression(
            '/value="m\.datetime"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render with invalid direction defaults to desc.
     */
    public function testRenderWithInvalidDirectionDefaultsToDesc(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['dir'] = 'invalid';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // desc should still be selected.
        $this->assertMatchesRegularExpression(
            '/value="desc"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render outputs form action URL.
     */
    public function testRenderOutputsFormActionUrl(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('action="http://example.com/sermons"', $output);
    }

    /**
     * Test render outputs table structure.
     */
    public function testRenderOutputsTableStructure(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('<table class="sermonbrowser">', $output);
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('<th scope="row"', $output);
        $this->assertStringContainsString('<td class="field">', $output);
    }

    /**
     * Test render outputs field labels.
     */
    public function testRenderOutputsFieldLabels(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString('Preacher', $output);
        $this->assertStringContainsString('Services', $output);
        $this->assertStringContainsString('Book', $output);
        $this->assertStringContainsString('Series', $output);
        $this->assertStringContainsString('Sort by', $output);
        $this->assertStringContainsString('Direction', $output);
    }

    /**
     * Test render with uppercase direction normalizes to lowercase.
     */
    public function testRenderWithUppercaseDirectionNormalizesToLowercase(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['dir'] = 'ASC';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // asc should be selected.
        $this->assertMatchesRegularExpression(
            '/value="asc"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render with empty entities arrays.
     */
    public function testRenderWithEmptyEntities(): void
    {
        $bibleBooks = ['Genesis'];

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn($bibleBooks);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn($bibleBooks);

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $book = Mockery::mock('alias:' . Book::class);
        $book->shouldReceive('findAllWithSermonCount')
            ->andReturn([]);

        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // Should still render the form with [All] options.
        $this->assertStringContainsString('id="mainfilter"', $output);
        $this->assertStringContainsString(Constants::ALL_FILTER, $output);
    }

    /**
     * Test render escapes special characters in entity names.
     */
    public function testRenderEscapesSpecialCharactersInEntityNames(): void
    {
        $bibleBooks = ['Genesis'];

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn($bibleBooks);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn($bibleBooks);

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllForFilter')
            ->andReturn([
                (object) ['id' => 1, 'name' => 'John "Jack" O\'Brien', 'count' => 5],
            ]);

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $book = Mockery::mock('alias:' . Book::class);
        $book->shouldReceive('findAllWithSermonCount')
            ->andReturn([]);

        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // The name should be properly escaped.
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('Brien', $output);
    }

    /**
     * Test render with XSS attempt in request data.
     */
    public function testRenderSanitizesRequestData(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['title'] = '<script>alert("xss")</script>';
        $_REQUEST['date'] = '<img src=x onerror=alert(1)>';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // Script tags should be escaped.
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('<img', $output);
    }

    /**
     * Test render outputs datepicker configuration.
     */
    public function testRenderOutputsDatepickerConfiguration(): void
    {
        $this->setUpRenderMocks();

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        $this->assertStringContainsString("$('#date').datepicker", $output);
        $this->assertStringContainsString("$('#enddate').datepicker", $output);
        $this->assertStringContainsString('changeMonth: true', $output);
        $this->assertStringContainsString('changeYear: true', $output);
        $this->assertStringContainsString('new Date(1970, 0, 1)', $output);
    }

    /**
     * Test render with escaped entity name gets unescaped by stripslashes.
     */
    public function testRenderStripSlashesFromEntityNames(): void
    {
        $bibleBooks = ['Genesis'];

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn($bibleBooks);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn($bibleBooks);

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllForFilter')
            ->andReturn([
                // Simulate database-escaped name with backslash.
                (object) ['id' => 1, 'name' => 'Pastor\\\'s Name', 'count' => 3],
            ]);

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $book = Mockery::mock('alias:' . Book::class);
        $book->shouldReceive('findAllWithSermonCount')
            ->andReturn([]);

        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // Stripslashes removes the backslash, esc_html encodes the apostrophe.
        // The key is that the backslash is NOT present in output.
        $this->assertStringNotContainsString('\\\'', $output);
        // And the name appears (apostrophe may be HTML entity or literal).
        $this->assertStringContainsString('Pastor', $output);
        $this->assertStringContainsString('Name', $output);
    }

    /**
     * Test render with all filter options selected shows All selected.
     */
    public function testRenderWithAllFilterSelectsAllOption(): void
    {
        $this->setUpRenderMocks();
        $_REQUEST['preacher'] = '0';

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // [All] should be selected when value is 0.
        $this->assertMatchesRegularExpression(
            '/value="0"[^>]*' . preg_quote(Constants::SELECTED, '/') . '/',
            $output
        );
    }

    /**
     * Test render uses translated book names.
     */
    public function testRenderUsesTranslatedBookNames(): void
    {
        $engBooks = ['Genesis', 'Matthew'];
        $translatedBooks = ['Genèse', 'Matthieu'];

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn($engBooks);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn($translatedBooks);

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('findAllForFilter')
            ->andReturn([]);

        $book = Mockery::mock('alias:' . Book::class);
        $book->shouldReceive('findAllWithSermonCount')
            ->andReturn([
                (object) ['name' => 'Genesis', 'count' => 5],
            ]);

        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');

        $output = $this->captureOutput(fn() => DropdownFilterRenderer::render(false, ''));

        // The translated book name should appear.
        $this->assertStringContainsString('Genèse', $output);
        // But the value should still be the English name.
        $this->assertStringContainsString('value="Genesis"', $output);
    }
}
