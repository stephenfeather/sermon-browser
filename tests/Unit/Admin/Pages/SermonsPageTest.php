<?php

/**
 * Tests for SermonsPage class.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Pages
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Pages;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Pages\SermonsPage;
use SermonBrowser\Repositories\SermonRepository;
use SermonBrowser\Repositories\PreacherRepository;
use SermonBrowser\Repositories\SeriesRepository;
use SermonBrowser\Repositories\TagRepository;
use SermonBrowser\Repositories\BookRepository;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test SermonsPage functionality.
 */
class SermonsPageTest extends TestCase
{
    /**
     * SermonsPage instance under test.
     *
     * @var SermonsPage
     */
    private SermonsPage $page;

    /**
     * Mock sermon repository.
     *
     * @var \Mockery\MockInterface&SermonRepository
     */
    private $mockSermonRepo;

    /**
     * Mock preacher repository.
     *
     * @var \Mockery\MockInterface&PreacherRepository
     */
    private $mockPreacherRepo;

    /**
     * Mock series repository.
     *
     * @var \Mockery\MockInterface&SeriesRepository
     */
    private $mockSeriesRepo;

    /**
     * Mock tag repository.
     *
     * @var \Mockery\MockInterface&TagRepository
     */
    private $mockTagRepo;

    /**
     * Mock book repository.
     *
     * @var \Mockery\MockInterface&BookRepository
     */
    private $mockBookRepo;

    /**
     * Mock file repository.
     *
     * @var \Mockery\MockInterface&FileRepository
     */
    private $mockFileRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define plugin URL constant if not already defined.
        if (!defined('SB_PLUGIN_URL')) {
            define('SB_PLUGIN_URL', 'http://example.com/wp-content/plugins/sermon-browser');
        }

        // Reset and configure container with mocks.
        Container::reset();
        $this->mockSermonRepo = Mockery::mock(SermonRepository::class);
        $this->mockPreacherRepo = Mockery::mock(PreacherRepository::class);
        $this->mockSeriesRepo = Mockery::mock(SeriesRepository::class);
        $this->mockTagRepo = Mockery::mock(TagRepository::class);
        $this->mockBookRepo = Mockery::mock(BookRepository::class);
        $this->mockFileRepo = Mockery::mock(FileRepository::class);

        Container::getInstance()->set(SermonRepository::class, $this->mockSermonRepo);
        Container::getInstance()->set(PreacherRepository::class, $this->mockPreacherRepo);
        Container::getInstance()->set(SeriesRepository::class, $this->mockSeriesRepo);
        Container::getInstance()->set(TagRepository::class, $this->mockTagRepo);
        Container::getInstance()->set(BookRepository::class, $this->mockBookRepo);
        Container::getInstance()->set(FileRepository::class, $this->mockFileRepo);

        // Common WordPress function stubs.
        $this->stubCommonWordPressFunctions();

        $this->page = new SermonsPage();
    }

    /**
     * Stub common WordPress functions.
     */
    private function stubCommonWordPressFunctions(): void
    {
        Functions\stubs([
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'admin_url' => static function (string $path = ''): string {
                return 'http://example.com/wp-admin/' . ltrim($path, '/');
            },
            'wp_nonce_url' => static function (string $url, string $action, string $name): string {
                return $url . '&' . $name . '=nonce123';
            },
            'wp_date' => static function (string $format, int $timestamp): string {
                return date($format, $timestamp);
            },
            'selected' => static function ($selected, $current, bool $echo = true): string {
                $result = $selected == $current ? ' selected="selected"' : '';
                if ($echo) {
                    echo $result;
                }
                return $result;
            },
            'sb_display_url' => static fn() => 'http://example.com/sermons',
            'sb_query_char' => static fn() => '?',
            'sb_sermon_stats' => static fn() => '<span>Views: 10</span>',
            'sb_get_option' => static fn($key) => match ($key) {
                'sermons_per_page' => 10,
                'show_donate_reminder' => 'off',
                default => ''
            },
            'wp_unslash' => static fn($value) => stripslashes((string) $value),
        ]);
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
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        $_GET = [];
        Container::reset();
        parent::tearDown();
    }

    /**
     * Test render dies when user lacks permissions.
     */
    public function testRenderDiesWhenUserLacksPermissions(): void
    {
        Functions\expect('current_user_can')
            ->with('publish_posts')
            ->once()
            ->andReturn(false);

        Functions\expect('current_user_can')
            ->with('publish_pages')
            ->once()
            ->andReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with('You do not have the correct permissions to edit sermons')
            ->andReturnUsing(function () {
                throw new \RuntimeException('wp_die called');
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        $this->page->render();
    }

    /**
     * Test render calls sb_do_alerts.
     */
    public function testRenderCallsDoAlerts(): void
    {
        Functions\when('current_user_can')->justReturn(true);

        Functions\expect('sb_do_alerts')
            ->once();

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render displays saved message when saved param present.
     */
    public function testRenderDisplaysSavedMessage(): void
    {
        $_GET['saved'] = '1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Sermon saved to database', $output);
    }

    /**
     * Test render handles deletion when mid param present with valid nonce.
     */
    public function testRenderHandlesDeletion(): void
    {
        $_GET['mid'] = '42';
        $_GET['sermon_manage_sermons_nonce'] = 'valid_nonce';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        Functions\expect('wp_verify_nonce')
            ->with('valid_nonce', 'sermon_manage_sermons')
            ->once()
            ->andReturn(1);

        Functions\expect('sb_delete_unused_tags')
            ->once();

        $this->mockSermonRepo->shouldReceive('delete')
            ->with(42)
            ->once();

        $this->mockTagRepo->shouldReceive('detachAllFromSermon')
            ->with(42)
            ->once();

        $this->mockBookRepo->shouldReceive('deleteBySermonId')
            ->with(42)
            ->once();

        $this->mockFileRepo->shouldReceive('unlinkFromSermon')
            ->with(42)
            ->once();

        $this->mockFileRepo->shouldReceive('deleteNonFilesBySermon')
            ->with(42)
            ->once();

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Sermon removed from database', $output);
    }

    /**
     * Test render dies when deletion nonce is invalid.
     */
    public function testRenderDiesWhenDeletionNonceInvalid(): void
    {
        $_GET['mid'] = '42';
        $_GET['sermon_manage_sermons_nonce'] = 'invalid_nonce';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        Functions\expect('wp_verify_nonce')
            ->with('invalid_nonce', 'sermon_manage_sermons')
            ->once()
            ->andReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with('You do not have the correct permissions to edit sermons')
            ->andReturnUsing(function () {
                throw new \RuntimeException('wp_die called');
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        $this->page->render();
    }

    /**
     * Test render extracts title filter from GET.
     */
    public function testRenderExtractsTitleFilter(): void
    {
        $_GET['title'] = 'Test Search';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with(['title' => 'Test Search'], 10)
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render extracts preacher filter from GET.
     */
    public function testRenderExtractsPreacherFilter(): void
    {
        $_GET['preacher_id'] = '5';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with(['preacher_id' => 5], 10)
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render extracts series filter from GET.
     */
    public function testRenderExtractsSeriesFilter(): void
    {
        $_GET['series_id'] = '3';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with(['series_id' => 3], 10)
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render displays filter form.
     */
    public function testRenderDisplaysFilterForm(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([
            (object) ['id' => 1, 'name' => 'John Doe'],
            (object) ['id' => 2, 'name' => 'Jane Smith'],
        ]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([
            (object) ['id' => 1, 'name' => 'Genesis Series'],
        ]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('<form id="searchform"', $output);
        $this->assertStringContainsString('Filter', $output);
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('Jane Smith', $output);
        $this->assertStringContainsString('Genesis Series', $output);
    }

    /**
     * Test render displays sermons table.
     */
    public function testRenderDisplaysSermonsTable(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(1);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 1,
                'title' => 'Test Sermon Title',
                'pname' => 'Pastor John',
                'datetime' => '2024-01-15 10:00:00',
                'sname' => 'Morning Service',
                'ssname' => 'Romans Series',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('<table class="widefat">', $output);
        $this->assertStringContainsString('Test Sermon Title', $output);
        $this->assertStringContainsString('Pastor John', $output);
        $this->assertStringContainsString('Morning Service', $output);
        $this->assertStringContainsString('Romans Series', $output);
    }

    /**
     * Test render displays unknown date for epoch datetime.
     */
    public function testRenderDisplaysUnknownDateForEpochDatetime(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(1);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 1,
                'title' => 'Old Sermon',
                'pname' => 'Unknown Preacher',
                'datetime' => '1970-01-01 00:00:00',
                'sname' => '',
                'ssname' => '',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Unknown', $output);
    }

    /**
     * Test render displays edit and delete links for authorized users.
     */
    public function testRenderDisplaysEditAndDeleteLinksForAuthorizedUsers(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(1);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 42,
                'title' => 'Test Sermon',
                'pname' => 'Pastor',
                'datetime' => '2024-01-15 10:00:00',
                'sname' => '',
                'ssname' => '',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('Delete', $output);
        $this->assertStringContainsString('View', $output);
        $this->assertStringContainsString('mid=42', $output);
    }

    /**
     * Test render displays navigation when more sermons than per page.
     */
    public function testRenderDisplaysNavigationWhenMoreSermons(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(25);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Next', $output);
        $this->assertStringContainsString('fetch(', $output);
    }

    /**
     * Test render displays logo.
     */
    public function testRenderDisplaysLogo(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('logo-small.png', $output);
        $this->assertStringContainsString('sermonbrowser.com', $output);
    }

    /**
     * Test render includes JavaScript for AJAX pagination.
     */
    public function testRenderIncludesJavascript(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('SBAdmin.sermon.list', $output);
        $this->assertStringContainsString('currentSermonPage', $output);
    }

    /**
     * Test render displays table headers.
     */
    public function testRenderDisplaysTableHeaders(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Title', $output);
        $this->assertStringContainsString('Preacher', $output);
        $this->assertStringContainsString('Date', $output);
        $this->assertStringContainsString('Service', $output);
        $this->assertStringContainsString('Series', $output);
        $this->assertStringContainsString('Stats', $output);
        $this->assertStringContainsString('Actions', $output);
    }

    /**
     * Test render alternates row classes.
     */
    public function testRenderAlternatesRowClasses(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(2);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 1,
                'title' => 'First Sermon',
                'pname' => 'Pastor',
                'datetime' => '2024-01-15 10:00:00',
                'sname' => '',
                'ssname' => '',
            ],
            (object) [
                'id' => 2,
                'title' => 'Second Sermon',
                'pname' => 'Pastor',
                'datetime' => '2024-01-16 10:00:00',
                'sname' => '',
                'ssname' => '',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('class="alternate"', $output);
        $this->assertStringContainsString('class=""', $output);
    }

    /**
     * Test render ignores zero preacher_id filter.
     */
    public function testRenderIgnoresZeroPreacherIdFilter(): void
    {
        $_GET['preacher_id'] = '0';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with([], 10) // Empty filters, not ['preacher_id' => 0]
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render ignores zero series_id filter.
     */
    public function testRenderIgnoresZeroSeriesIdFilter(): void
    {
        $_GET['series_id'] = '0';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with([], 10) // Empty filters, not ['series_id' => 0]
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render ignores empty title filter.
     */
    public function testRenderIgnoresEmptyTitleFilter(): void
    {
        $_GET['title'] = '';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with([], 10) // Empty filters
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render applies multiple filters.
     */
    public function testRenderAppliesMultipleFilters(): void
    {
        $_GET['title'] = 'Romans';
        $_GET['preacher_id'] = '5';
        $_GET['series_id'] = '3';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')
            ->with([
                'title' => 'Romans',
                'preacher_id' => 5,
                'series_id' => 3,
            ], 10)
            ->once()
            ->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render displays sermon stats.
     */
    public function testRenderDisplaysSermonStats(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(1);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'pname' => 'Pastor',
                'datetime' => '2024-01-15 10:00:00',
                'sname' => '',
                'ssname' => '',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Views: 10', $output);
    }

    /**
     * Test render pre-selects current preacher filter in dropdown.
     */
    public function testRenderPreselectsCurrentPreacherFilter(): void
    {
        $_GET['preacher_id'] = '2';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([
            (object) ['id' => 1, 'name' => 'John'],
            (object) ['id' => 2, 'name' => 'Jane'],
        ]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        // The selected() function should output selected="selected" for id=2
        $this->assertStringContainsString('value="2" selected="selected"', $output);
    }

    /**
     * Test render pre-selects current series filter in dropdown.
     */
    public function testRenderPreselectsCurrentSeriesFilter(): void
    {
        $_GET['series_id'] = '1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([
            (object) ['id' => 1, 'name' => 'Genesis'],
            (object) ['id' => 2, 'name' => 'Exodus'],
        ]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('value="1" selected="selected"', $output);
    }

    /**
     * Test render pre-fills title search field.
     */
    public function testRenderPrefillsTitleSearchField(): void
    {
        $_GET['title'] = 'Search Term';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('value="Search Term"', $output);
    }

    /**
     * Test render escapes special characters in preacher name.
     */
    public function testRenderEscapesSpecialCharactersInPreacherName(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([
            (object) ['id' => 1, 'name' => 'O\'Brien & Sons'],
        ]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        // htmlspecialchars with ENT_QUOTES encodes ' as &#039;
        $this->assertStringContainsString('O&#039;Brien &amp; Sons', $output);
    }

    /**
     * Test render shows delete confirmation dialog.
     */
    public function testRenderShowsDeleteConfirmation(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(1);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 1,
                'title' => 'Test',
                'pname' => 'Pastor',
                'datetime' => '2024-01-15 10:00:00',
                'sname' => '',
                'ssname' => '',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString("confirm('Are you sure?')", $output);
    }

    /**
     * Test render links to sermon view page.
     */
    public function testRenderLinksToSermonViewPage(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(1);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([
            (object) [
                'id' => 42,
                'title' => 'Test',
                'pname' => 'Pastor',
                'datetime' => '2024-01-15 10:00:00',
                'sname' => '',
                'ssname' => '',
            ],
        ]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('http://example.com/sermons?sermon_id=42', $output);
    }

    /**
     * Test render allowed with publish_pages capability.
     */
    public function testRenderAllowedWithPublishPagesCapability(): void
    {
        // User can only publish_pages, not publish_posts
        Functions\when('current_user_can')->alias(function ($cap) {
            return match ($cap) {
                'publish_posts' => false,
                'publish_pages' => true,
                default => false
            };
        });

        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        // Should render page without dying
        $this->assertStringContainsString('Sermons', $output);
    }

    /**
     * Test deletion dies when user lacks publish_posts capability.
     */
    public function testDeletionDiesWhenUserLacksPublishPostsCapability(): void
    {
        $_GET['mid'] = '42';
        $_GET['sermon_manage_sermons_nonce'] = 'valid_nonce';

        // User can publish_pages (passes initial check) but NOT publish_posts (fails delete check)
        $callCount = 0;
        Functions\when('current_user_can')->alias(function ($cap) use (&$callCount) {
            $callCount++;
            // First call is publish_posts for initial check - return false
            // Second call is publish_pages for initial check - return true (passes OR)
            // Third call is publish_posts for delete check - return false
            if ($cap === 'publish_pages') {
                return true;
            }
            return false; // publish_posts always returns false
        });

        Functions\when('sb_do_alerts')->justReturn(null);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->andReturn(1);

        Functions\expect('wp_die')
            ->once()
            ->with('You do not have the correct permissions to delete sermons')
            ->andReturnUsing(function () {
                throw new \RuntimeException('wp_die called');
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        $this->page->render();
    }

    /**
     * Test render displays empty table when no sermons.
     */
    public function testRenderDisplaysEmptyTableWhenNoSermons(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $this->mockSermonRepo->shouldReceive('count')->andReturn(0);
        $this->mockSermonRepo->shouldReceive('findForAdminListFiltered')->andReturn([]);
        $this->mockPreacherRepo->shouldReceive('findAllSorted')->andReturn([]);
        $this->mockSeriesRepo->shouldReceive('findAllSorted')->andReturn([]);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('<tbody id="the-list">', $output);
        $this->assertStringContainsString('</tbody>', $output);
    }
}
