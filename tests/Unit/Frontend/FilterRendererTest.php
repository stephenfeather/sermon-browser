<?php

/**
 * Tests for FilterRenderer class.
 *
 * @package SermonBrowser\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Frontend;

use Mockery;
use SermonBrowser\Config\Defaults;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Facades\Book;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Sermon;
use SermonBrowser\Frontend\DropdownFilterRenderer;
use SermonBrowser\Frontend\FilterRenderer;
use SermonBrowser\Frontend\PageResolver;
use SermonBrowser\Frontend\UrlBuilder;
use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test FilterRenderer functionality.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class FilterRendererTest extends TestCase
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
     * Sample date data for tests.
     *
     * @var array<object>
     */
    private array $sampleDates;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear superglobals before each test.
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];

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

        $this->sampleDates = [
            (object) ['year' => 2024, 'month' => 1, 'day' => 15],
            (object) ['year' => 2024, 'month' => 2, 'day' => 10],
            (object) ['year' => 2024, 'month' => 3, 'day' => 5],
        ];

        // Stub additional WordPress functions needed for rendering.
        Functions\stubs([
            'esc_html_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'esc_js' => static fn($text) => addslashes((string) $text),
            'wp_date' => static fn($format, $timestamp) => date($format, $timestamp),
        ]);
    }

    /**
     * Tear down test fixtures.
     */
    protected function tearDown(): void
    {
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        parent::tearDown();
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

    // =========================================================================
    // Tests for render() method
    // =========================================================================

    /**
     * Test render with dropdown filter type delegates to DropdownFilterRenderer.
     */
    public function testRenderWithDropdownFilterDelegatesToDropdownRenderer(): void
    {
        $dropdownRenderer = Mockery::mock('alias:' . DropdownFilterRenderer::class);
        $dropdownRenderer->shouldReceive('render')
            ->once()
            ->with(false, '');

        FilterRenderer::render(['filter' => 'dropdown']);
    }

    /**
     * Test render with dropdown filter and hide option.
     */
    public function testRenderWithDropdownFilterAndHideOption(): void
    {
        $dropdownRenderer = Mockery::mock('alias:' . DropdownFilterRenderer::class);
        $dropdownRenderer->shouldReceive('render')
            ->once()
            ->with(true, Mockery::type('string'));

        FilterRenderer::render(['filter' => 'dropdown', 'filterhide' => 'hide']);
    }

    /**
     * Test render with empty filter array outputs nothing.
     */
    public function testRenderWithEmptyFilterOutputsNothing(): void
    {
        $output = $this->captureOutput(fn() => FilterRenderer::render([]));

        $this->assertEmpty($output);
    }

    /**
     * Test render with unknown filter type outputs nothing.
     */
    public function testRenderWithUnknownFilterTypeOutputsNothing(): void
    {
        $output = $this->captureOutput(fn() => FilterRenderer::render(['filter' => 'unknown']));

        $this->assertEmpty($output);
    }

    // =========================================================================
    // Tests for renderLine() method
    // =========================================================================

    /**
     * Test renderLine outputs filter div with correct ID.
     */
    public function testRenderLineOutputsFilterDivWithCorrectId(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'Test Item', 'count' => 5],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name')
        );

        $this->assertStringContainsString('id = "preacher"', $output);
        $this->assertStringContainsString('class="filter"', $output);
    }

    /**
     * Test renderLine outputs filter heading.
     */
    public function testRenderLineOutputsFilterHeading(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'Test Item', 'count' => 5],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name')
        );

        $this->assertStringContainsString('class="filter-heading"', $output);
        $this->assertStringContainsString('Preacher:', $output);
    }

    /**
     * Test renderLine outputs links with correct URLs.
     */
    public function testRenderLineOutputsLinksWithCorrectUrls(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'John Smith', 'count' => 10],
            (object) ['id' => 2, 'name' => 'Jane Doe', 'count' => 5],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name')
        );

        $this->assertStringContainsString('John Smith', $output);
        $this->assertStringContainsString('(10)', $output);
        $this->assertStringContainsString('Jane Doe', $output);
        $this->assertStringContainsString('(5)', $output);
        $this->assertStringContainsString('<a href=', $output);
    }

    /**
     * Test renderLine adds comma between items.
     */
    public function testRenderLineAddsCommaBetweenItems(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'First', 'count' => 3],
            (object) ['id' => 2, 'name' => 'Second', 'count' => 2],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('service', $results, 'id', 'name')
        );

        $this->assertMatchesRegularExpression('/First.*,.*Second/s', $output);
    }

    /**
     * Test renderLine shows "more" link when exceeding maxNum.
     */
    public function testRenderLineShowsMoreLinkWhenExceedingMaxNum(): void
    {
        $this->setUpUrlBuilderMock();

        // Create 10 items to exceed default maxNum of 7.
        $results = [];
        for ($i = 1; $i <= 10; $i++) {
            $results[] = (object) ['id' => $i, 'name' => "Item $i", 'count' => $i];
        }

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name', 7)
        );

        $this->assertStringContainsString('preacher-more', $output);
        $this->assertStringContainsString('preacher-more-link', $output);
        $this->assertStringContainsString('preacher-toggle', $output);
        $this->assertStringContainsString('more', $output);
    }

    /**
     * Test renderLine does not show "more" link when under maxNum.
     */
    public function testRenderLineDoesNotShowMoreLinkWhenUnderMaxNum(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'Item 1', 'count' => 5],
            (object) ['id' => 2, 'name' => 'Item 2', 'count' => 3],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name', 7)
        );

        $this->assertStringNotContainsString('preacher-more', $output);
        $this->assertStringNotContainsString('more', $output);
    }

    /**
     * Test renderLine translates book names.
     */
    public function testRenderLineTranslatesBookNames(): void
    {
        $this->setUpUrlBuilderMock();

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn(['Genesis', 'Matthew']);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn(['Genèse', 'Matthieu']);

        $results = [
            (object) ['name' => 'Genesis', 'count' => 5],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('book', $results, 'name', 'name')
        );

        $this->assertStringContainsString('Genèse', $output);
    }

    /**
     * Test renderLine uses stripslashes on display values.
     */
    public function testRenderLineUsesStripslashesOnDisplayValues(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'Pastor\\\'s Name', 'count' => 3],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name')
        );

        $this->assertStringNotContainsString('\\\'', $output);
        $this->assertStringContainsString('Pastor', $output);
    }

    /**
     * Test renderLine ends with period.
     */
    public function testRenderLineEndsWithPeriod(): void
    {
        $this->setUpUrlBuilderMock();

        $results = [
            (object) ['id' => 1, 'name' => 'Test', 'count' => 1],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name')
        );

        $this->assertStringContainsString('.</div>', $output);
    }

    // =========================================================================
    // Tests for renderDateLine() method
    // =========================================================================

    /**
     * Test renderDateLine with empty dates returns nothing.
     */
    public function testRenderDateLineWithEmptyDatesReturnsNothing(): void
    {
        $output = $this->captureOutput(
            fn() => FilterRenderer::renderDateLine([])
        );

        $this->assertEmpty($output);
    }

    /**
     * Test renderDateLine with same year and month returns nothing.
     */
    public function testRenderDateLineWithSameYearAndMonthReturnsNothing(): void
    {
        $dates = [
            (object) ['year' => 2024, 'month' => 3, 'day' => 1],
            (object) ['year' => 2024, 'month' => 3, 'day' => 15],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderDateLine($dates)
        );

        $this->assertEmpty($output);
    }

    /**
     * Test renderDateLine with same year different months shows monthly links.
     */
    public function testRenderDateLineWithSameYearDifferentMonthsShowsMonthlyLinks(): void
    {
        $this->setUpUrlBuilderMock();

        $dates = [
            (object) ['year' => 2024, 'month' => 1, 'day' => 15],
            (object) ['year' => 2024, 'month' => 1, 'day' => 20],
            (object) ['year' => 2024, 'month' => 2, 'day' => 10],
            (object) ['year' => 2024, 'month' => 3, 'day' => 5],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderDateLine($dates)
        );

        $this->assertStringContainsString('id = "dates"', $output);
        $this->assertStringContainsString('Date:', $output);
        // Should show month names.
        $this->assertStringContainsString('January', $output);
        $this->assertStringContainsString('February', $output);
        $this->assertStringContainsString('March', $output);
        // Should show counts.
        $this->assertStringContainsString('(2)', $output); // January has 2 entries.
        $this->assertStringContainsString('(1)', $output); // Feb and March have 1 each.
    }

    /**
     * Test renderDateLine with different years shows yearly links.
     */
    public function testRenderDateLineWithDifferentYearsShowsYearlyLinks(): void
    {
        $this->setUpUrlBuilderMock();

        $dates = [
            (object) ['year' => 2022, 'month' => 6, 'day' => 15],
            (object) ['year' => 2023, 'month' => 3, 'day' => 10],
            (object) ['year' => 2023, 'month' => 6, 'day' => 20],
            (object) ['year' => 2024, 'month' => 1, 'day' => 5],
        ];

        $output = $this->captureOutput(
            fn() => FilterRenderer::renderDateLine($dates)
        );

        $this->assertStringContainsString('2022', $output);
        $this->assertStringContainsString('2023', $output);
        $this->assertStringContainsString('2024', $output);
        $this->assertStringContainsString('(1)', $output); // 2022, 2024 have 1 each.
        $this->assertStringContainsString('(2)', $output); // 2023 has 2.
    }

    // =========================================================================
    // Tests for urlMinusParameter() method
    // =========================================================================

    /**
     * Test urlMinusParameter removes specified parameter.
     */
    public function testUrlMinusParameterRemovesSpecifiedParameter(): void
    {
        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');
        $pageResolver->shouldReceive('getQueryChar')
            ->andReturn('?');

        FilterRenderer::setFilterOptions(['preacher', 'book', 'series']);
        $_GET = ['preacher' => '1', 'book' => 'Genesis', 'series' => '2'];

        $url = FilterRenderer::urlMinusParameter('preacher');

        $this->assertStringContainsString('book=Genesis', $url);
        $this->assertStringContainsString('series=2', $url);
        $this->assertStringNotContainsString('preacher=', $url);
    }

    /**
     * Test urlMinusParameter removes two parameters.
     */
    public function testUrlMinusParameterRemovesTwoParameters(): void
    {
        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');
        $pageResolver->shouldReceive('getQueryChar')
            ->andReturn('?');

        FilterRenderer::setFilterOptions(['date', 'enddate', 'preacher']);
        $_GET = ['date' => '2024-01-01', 'enddate' => '2024-12-31', 'preacher' => '1'];

        $url = FilterRenderer::urlMinusParameter('date', 'enddate');

        $this->assertStringContainsString('preacher=1', $url);
        $this->assertStringNotContainsString('date=', $url);
        $this->assertStringNotContainsString('enddate=', $url);
    }

    /**
     * Test urlMinusParameter returns base URL when no parameters remain.
     */
    public function testUrlMinusParameterReturnsBaseUrlWhenNoParamsRemain(): void
    {
        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');

        FilterRenderer::setFilterOptions(['preacher']);
        $_GET = ['preacher' => '1'];

        $url = FilterRenderer::urlMinusParameter('preacher');

        $this->assertEquals('http://example.com/sermons', $url);
    }

    /**
     * Test urlMinusParameter only includes whitelisted filter options.
     */
    public function testUrlMinusParameterOnlyIncludesWhitelistedOptions(): void
    {
        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');
        $pageResolver->shouldReceive('getQueryChar')
            ->andReturn('?');

        FilterRenderer::setFilterOptions(['preacher', 'book']);
        $_GET = ['preacher' => '1', 'book' => 'Genesis', 'malicious' => 'data'];

        $url = FilterRenderer::urlMinusParameter('preacher');

        $this->assertStringContainsString('book=Genesis', $url);
        $this->assertStringNotContainsString('malicious', $url);
    }

    // =========================================================================
    // Tests for getMoreApplied() method
    // =========================================================================

    /**
     * Test getMoreApplied returns empty array initially.
     */
    public function testGetMoreAppliedReturnsEmptyArrayInitially(): void
    {
        // Call render to reset moreApplied.
        FilterRenderer::render([]);

        $result = FilterRenderer::getMoreApplied();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getMoreApplied returns element IDs after renderLine with overflow.
     */
    public function testGetMoreAppliedReturnsElementIdsAfterRenderLineWithOverflow(): void
    {
        $this->setUpUrlBuilderMock();

        // Reset by calling render with empty filter.
        FilterRenderer::render([]);

        // Create enough items to trigger "more" link.
        $results = [];
        for ($i = 1; $i <= 10; $i++) {
            $results[] = (object) ['id' => $i, 'name' => "Item $i", 'count' => $i];
        }

        $this->captureOutput(
            fn() => FilterRenderer::renderLine('preacher', $results, 'id', 'name', 5)
        );

        $moreApplied = FilterRenderer::getMoreApplied();

        $this->assertContains('preacher', $moreApplied);
    }

    // =========================================================================
    // Tests for setFilterOptions() and getFilterOptions() methods
    // =========================================================================

    /**
     * Test setFilterOptions and getFilterOptions work correctly.
     */
    public function testSetAndGetFilterOptions(): void
    {
        $options = ['preacher', 'book', 'series', 'date'];

        FilterRenderer::setFilterOptions($options);
        $result = FilterRenderer::getFilterOptions();

        $this->assertEquals($options, $result);
    }

    /**
     * Test setFilterOptions accepts empty array.
     */
    public function testSetFilterOptionsAcceptsEmptyArray(): void
    {
        FilterRenderer::setFilterOptions([]);
        $result = FilterRenderer::getFilterOptions();

        $this->assertEquals([], $result);
    }

    // =========================================================================
    // Tests for oneclick filter rendering
    // =========================================================================

    /**
     * Test render with oneclick filter outputs main container.
     */
    public function testRenderOneClickFilterOutputsMainContainer(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('id="mainfilter"', $output);
    }

    /**
     * Test render with oneclick filter outputs show/hide button.
     */
    public function testRenderOneClickFilterOutputsShowHideButton(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('id="show_hide_filter"', $output);
        $this->assertStringContainsString('class="button-link"', $output);
    }

    /**
     * Test render with oneclick filter shows preacher filter when multiple preachers.
     */
    public function testRenderOneClickFilterShowsPreacherFilterWhenMultiplePreachers(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('id = "preacher"', $output);
        $this->assertStringContainsString('John Smith', $output);
        $this->assertStringContainsString('Jane Doe', $output);
    }

    /**
     * Test render with oneclick filter shows book filter when multiple books.
     */
    public function testRenderOneClickFilterShowsBookFilterWhenMultipleBooks(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('id = "book"', $output);
        $this->assertStringContainsString('Genesis', $output);
        $this->assertStringContainsString('Matthew', $output);
    }

    /**
     * Test render with oneclick filter shows series filter when multiple series.
     */
    public function testRenderOneClickFilterShowsSeriesFilterWhenMultipleSeries(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('id = "series"', $output);
        $this->assertStringContainsString('Romans Study', $output);
        $this->assertStringContainsString('Psalms Series', $output);
    }

    /**
     * Test render with oneclick filter shows service filter when multiple services.
     */
    public function testRenderOneClickFilterShowsServiceFilterWhenMultipleServices(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('id = "service"', $output);
        $this->assertStringContainsString('Sunday Morning', $output);
        $this->assertStringContainsString('Wednesday Evening', $output);
    }

    /**
     * Test render with oneclick filter hides filter with single item.
     */
    public function testRenderOneClickFilterHidesFilterWithSingleItem(): void
    {
        $this->setUpOneClickMocks(singlePreacher: true);

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        // Should not show preacher filter when only 1 preacher.
        $this->assertStringNotContainsString('id = "preacher"', $output);
    }

    /**
     * Test render with oneclick filter and active filter shows filtered div.
     */
    public function testRenderOneClickFilterWithActiveFilterShowsFilteredDiv(): void
    {
        $this->setUpOneClickMocks();
        $_REQUEST['preacher'] = '1';

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('class="filtered"', $output);
        $this->assertStringContainsString('Active filter', $output);
        $this->assertStringContainsString('Preacher', $output);
    }

    /**
     * Test render with oneclick filter and hide option outputs hide JS.
     */
    public function testRenderOneClickFilterWithHideOptionOutputsHideJs(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick', 'filterhide' => 'hide'])
        );

        $this->assertStringContainsString('Show filter', $output);
        $this->assertStringContainsString('Hide filter', $output);
        $this->assertStringContainsString('filter_visible', $output);
    }

    /**
     * Test render with oneclick filter outputs JavaScript.
     */
    public function testRenderOneClickFilterOutputsJavaScript(): void
    {
        $this->setUpOneClickMocks();

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('jQuery', $output);
    }

    /**
     * Test render with oneclick filter and date request shows date in active filter.
     */
    public function testRenderOneClickFilterWithDateShowsDateInActiveFilter(): void
    {
        $this->setUpOneClickMocks();
        $_REQUEST['date'] = '2024-01-01';
        $_REQUEST['enddate'] = '2024-12-31';

        $output = $this->captureOutput(
            fn() => FilterRenderer::render(['filter' => 'oneclick'])
        );

        $this->assertStringContainsString('Date', $output);
        $this->assertStringContainsString('2024', $output);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Set up UrlBuilder mock.
     */
    private function setUpUrlBuilderMock(): void
    {
        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->andReturnUsing(function ($params) {
                return 'http://example.com/sermons?' . http_build_query($params);
            });
    }

    /**
     * Set up mocks for oneclick filter tests.
     */
    private function setUpOneClickMocks(bool $singlePreacher = false): void
    {
        // UrlBuilder mock.
        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('build')
            ->andReturnUsing(function ($params) {
                return 'http://example.com/sermons?' . http_build_query($params);
            });

        // PageResolver mock.
        $pageResolver = Mockery::mock('alias:' . PageResolver::class);
        $pageResolver->shouldReceive('getDisplayUrl')
            ->andReturn('http://example.com/sermons');
        $pageResolver->shouldReceive('getQueryChar')
            ->andReturn('?');

        // OptionsManager mock.
        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('hide_no_attachments')
            ->andReturn(false);

        // Define sb_get_sermons function.
        Functions\when('sb_get_sermons')->justReturn([
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 3],
        ]);

        // Preacher facade mock.
        $preachers = $singlePreacher
            ? [(object) ['id' => 1, 'name' => 'John Smith', 'count' => 10]]
            : $this->samplePreachers;

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findBySermonIdsWithCount')
            ->andReturn($preachers);

        // Series facade mock.
        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('findBySermonIdsWithCount')
            ->andReturn($this->sampleSeries);

        // Service facade mock.
        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('findBySermonIdsWithCount')
            ->andReturn($this->sampleServices);

        // Book facade mock.
        $book = Mockery::mock('alias:' . Book::class);
        $book->shouldReceive('findBySermonIdsWithCount')
            ->andReturn($this->sampleBookCount);

        // Sermon facade mock.
        $sermon = Mockery::mock('alias:' . Sermon::class);
        $sermon->shouldReceive('findDatesForIds')
            ->andReturn($this->sampleDates);

        // Defaults mock (for book translation).
        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn(['Genesis', 'Matthew']);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn(['Genesis', 'Matthew']);
    }
}
