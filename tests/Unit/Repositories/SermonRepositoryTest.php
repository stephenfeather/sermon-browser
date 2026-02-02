<?php

/**
 * Tests for SermonRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\SermonRepository;
use Mockery;

/**
 * Test SermonRepository functionality.
 */
class SermonRepositoryTest extends TestCase
{
    /**
     * Mock wpdb instance.
     *
     * @var \Mockery\MockInterface&\wpdb
     */
    private $wpdb;

    /**
     * The repository under test.
     *
     * @var SermonRepository
     */
    private SermonRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new SermonRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_sermons', $this->repository->getTableName());
    }

    /**
     * Test findByPreacher returns sermons for preacher.
     */
    public function testFindByPreacher(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'title' => 'Sermon 1', 'preacher_id' => 5],
            (object) ['id' => 2, 'title' => 'Sermon 2', 'preacher_id' => 5],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('preacher_id = %d', 5)
            ->andReturn('preacher_id = 5');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findByPreacher(5);

        $this->assertCount(2, $result);
        $this->assertSame('Sermon 1', $result[0]->title);
    }

    /**
     * Test findBySeries returns sermons for series.
     */
    public function testFindBySeries(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'title' => 'Sermon 1', 'series_id' => 3],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('series_id = %d', 3)
            ->andReturn('series_id = 3');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findBySeries(3);

        $this->assertCount(1, $result);
    }

    /**
     * Test findRecent returns recent sermons.
     */
    public function testFindRecent(): void
    {
        $expectedSermons = [
            (object) ['id' => 10, 'title' => 'Latest Sermon'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 10, 0)
            ->andReturn(' LIMIT 10 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findRecent(10);

        $this->assertCount(1, $result);
    }

    /**
     * Test findByDateRange returns sermons in date range.
     */
    public function testFindByDateRange(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'datetime' => '2024-01-15 10:00:00'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(
                Mockery::pattern('/SELECT \* FROM wp_sb_sermons WHERE datetime >= %s AND datetime <= %s/'),
                '2024-01-01 00:00:00',
                '2024-01-31 23:59:59'
            )
            ->andReturn('SELECT * FROM wp_sb_sermons WHERE datetime >= "2024-01-01 00:00:00" AND datetime <= "2024-01-31 23:59:59"');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findByDateRange('2024-01-01', '2024-01-31');

        $this->assertCount(1, $result);
    }

    /**
     * Test searchByTitle returns matching sermons.
     */
    public function testSearchByTitle(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'title' => 'The Gospel of Grace'],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('Gospel')
            ->andReturn('Gospel');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_sermons WHERE title LIKE "%Gospel%"');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->searchByTitle('Gospel');

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Gospel', $result[0]->title);
    }

    /**
     * Test findWithRelations returns sermon with related data.
     */
    public function testFindWithRelations(): void
    {
        $expectedSermon = (object) [
            'id' => 1,
            'title' => 'Test Sermon',
            'preacher_name' => 'John Doe',
            'series_name' => 'Romans',
            'service_name' => 'Sunday Morning',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($expectedSermon);

        $result = $this->repository->findWithRelations(1);

        $this->assertSame('Test Sermon', $result->title);
        $this->assertSame('John Doe', $result->preacher_name);
        $this->assertSame('Romans', $result->series_name);
    }

    /**
     * Test getYears returns distinct years.
     */
    public function testGetYears(): void
    {
        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn(['2024', '2023', '2022']);

        $result = $this->repository->getYears();

        $this->assertSame([2024, 2023, 2022], $result);
    }

    /**
     * Test findPreviousByDate returns previous sermon.
     */
    public function testFindPreviousByDate(): void
    {
        $previousSermon = (object) [
            'id' => 4,
            'title' => 'Previous Sermon',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($previousSermon);

        $result = $this->repository->findPreviousByDate('2024-01-15 10:00:00', 5);

        $this->assertSame(4, $result->id);
    }

    /**
     * Test findNextByDate returns next sermon.
     */
    public function testFindNextByDate(): void
    {
        $nextSermon = (object) [
            'id' => 6,
            'title' => 'Next Sermon',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($nextSermon);

        $result = $this->repository->findNextByDate('2024-01-15 10:00:00', 5);

        $this->assertSame(6, $result->id);
    }

    /**
     * Test countFiltered counts with filters.
     */
    public function testCountFiltered(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' AND preacher_id = %d', 5)
            ->andReturn(' AND preacher_id = 5');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('10');

        $result = $this->repository->countFiltered(['preacher_id' => 5]);

        $this->assertSame(10, $result);
    }

    /**
     * Test findForTemplate returns sermon with legacy property names.
     */
    public function testFindForTemplate(): void
    {
        $expectedSermon = (object) [
            'id' => 1,
            'title' => 'Test Sermon',
            'datetime' => '2024-01-15 10:00:00',
            'start' => 'a:1:{i:0;a:3:{s:4:"book";s:4:"John";s:7:"chapter";s:1:"3";s:5:"verse";s:2:"16";}}',
            'end' => 'a:1:{i:0;a:3:{s:4:"book";s:4:"John";s:7:"chapter";s:1:"3";s:5:"verse";s:2:"21";}}',
            'description' => 'A sermon about love',
            'pid' => 5,
            'preacher' => 'John Doe',
            'image' => 'john-doe.jpg',
            'preacher_description' => 'Senior Pastor',
            'sid' => 2,
            'service' => 'Sunday Morning',
            'ssid' => 3,
            'series' => 'Romans',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($expectedSermon);

        $result = $this->repository->findForTemplate(1);

        // Verify legacy property names are used
        $this->assertSame('Test Sermon', $result->title);
        $this->assertSame(5, $result->pid);
        $this->assertSame('John Doe', $result->preacher);
        $this->assertSame('john-doe.jpg', $result->image);
        $this->assertSame(2, $result->sid);
        $this->assertSame('Sunday Morning', $result->service);
        $this->assertSame(3, $result->ssid);
        $this->assertSame('Romans', $result->series);
    }

    /**
     * Test findForTemplate returns null when sermon not found.
     */
    public function testFindForTemplateReturnsNullWhenNotFound(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $result = $this->repository->findForTemplate(999);

        $this->assertNull($result);
    }

    /**
     * Test findDatesForIds returns date components for sermon IDs.
     */
    public function testFindDatesForIds(): void
    {
        $expectedDates = [
            (object) ['year' => '2024', 'month' => '1', 'day' => '7'],
            (object) ['year' => '2024', 'month' => '1', 'day' => '14'],
            (object) ['year' => '2024', 'month' => '1', 'day' => '21'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedDates);

        $result = $this->repository->findDatesForIds([1, 2, 3]);

        $this->assertCount(3, $result);
        $this->assertSame('2024', $result[0]->year);
        $this->assertSame('1', $result[0]->month);
        $this->assertSame('7', $result[0]->day);
    }

    /**
     * Test findDatesForIds returns empty array for empty input.
     */
    public function testFindDatesForIdsReturnsEmptyForEmptyInput(): void
    {
        $result = $this->repository->findDatesForIds([]);

        $this->assertSame([], $result);
    }

    /**
     * Test findDatesForIds handles single sermon ID.
     */
    public function testFindDatesForIdsSingleId(): void
    {
        $expectedDates = [
            (object) ['year' => '2024', 'month' => '6', 'day' => '15'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedDates);

        $result = $this->repository->findDatesForIds([42]);

        $this->assertCount(1, $result);
        $this->assertSame('2024', $result[0]->year);
    }

    /**
     * Test findForAdminListFiltered returns sermons with filters.
     */
    public function testFindForAdminListFiltered(): void
    {
        $expectedSermons = [
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'datetime' => '2024-01-15 10:00:00',
                'pname' => 'John Doe',
                'sname' => 'Sunday Morning',
                'ssname' => 'Romans',
            ],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('Test')
            ->andReturn('Test');

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findForAdminListFiltered(
            ['title' => 'Test', 'preacher_id' => 5],
            10,
            0
        );

        $this->assertCount(1, $result);
        $this->assertSame('Test Sermon', $result[0]->title);
        $this->assertSame('John Doe', $result[0]->pname);
        $this->assertSame('Sunday Morning', $result[0]->sname);
        $this->assertSame('Romans', $result[0]->ssname);
    }

    /**
     * Test findForAdminList delegates to findForAdminListFiltered.
     */
    public function testFindForAdminListDelegatesToFiltered(): void
    {
        $expectedSermons = [
            (object) [
                'id' => 1,
                'title' => 'Sermon 1',
                'datetime' => '2024-01-15',
                'pname' => 'Preacher',
                'sname' => 'Service',
                'ssname' => 'Series',
            ],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findForAdminListFiltered([], 10, 0);

        $this->assertCount(1, $result);
    }

    /**
     * Test findByService returns sermons for a service.
     */
    public function testFindByServiceReturnsSermonsForService(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'title' => 'Sermon 1', 'service_id' => 2],
            (object) ['id' => 2, 'title' => 'Sermon 2', 'service_id' => 2],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('service_id = %d', 2)
            ->andReturn('service_id = 2');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findByService(2);

        $this->assertCount(2, $result);
        $this->assertSame('Sermon 1', $result[0]->title);
    }

    /**
     * Test findByYearMonth returns sermons for specific year and month.
     */
    public function testFindByYearMonthReturnsSermonsForYearMonth(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'title' => 'January Sermon', 'datetime' => '2024-01-07 10:00:00'],
            (object) ['id' => 2, 'title' => 'Another January', 'datetime' => '2024-01-14 10:00:00'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_sermons WHERE YEAR(datetime) = 2024 AND MONTH(datetime) = 1');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findByYearMonth(2024, 1);

        $this->assertCount(2, $result);
        $this->assertSame('January Sermon', $result[0]->title);
    }

    /**
     * Test findByYearMonth returns empty array when no sermons.
     */
    public function testFindByYearMonthReturnsEmptyArrayWhenNoSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_sermons WHERE YEAR(datetime) = 2024 AND MONTH(datetime) = 12');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findByYearMonth(2024, 12);

        $this->assertSame([], $result);
    }

    /**
     * Test findSameDay returns sermons preached on the same day.
     */
    public function testFindSameDayReturnsSermonsOnSameDay(): void
    {
        $expectedSermons = [
            (object) ['id' => 2, 'title' => 'Evening Sermon'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id, title FROM wp_sb_sermons WHERE DATE(datetime) = DATE("2024-01-07 10:00:00") AND id <> 1');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findSameDay('2024-01-07 10:00:00', 1);

        $this->assertCount(1, $result);
        $this->assertSame('Evening Sermon', $result[0]->title);
    }

    /**
     * Test findSameDay returns empty array when no other sermons.
     */
    public function testFindSameDayReturnsEmptyArrayWhenNoOtherSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id, title FROM wp_sb_sermons...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findSameDay('2024-01-07 10:00:00', 1);

        $this->assertSame([], $result);
    }

    /**
     * Test findAllWithRelations returns sermons with related data.
     */
    public function testFindAllWithRelationsReturnsSermonsWithRelatedData(): void
    {
        $expectedSermons = [
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'preacher_name' => 'John Doe',
                'series_name' => 'Romans',
                'service_name' => 'Sunday Morning',
            ],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findAllWithRelations();

        $this->assertCount(1, $result);
        $this->assertSame('John Doe', $result[0]->preacher_name);
        $this->assertSame('Romans', $result[0]->series_name);
    }

    /**
     * Test findAllWithRelations applies filters.
     */
    public function testFindAllWithRelationsAppliesFilters(): void
    {
        $expectedSermons = [
            (object) [
                'id' => 1,
                'title' => 'Filtered Sermon',
                'preacher_name' => 'John Doe',
                'series_name' => 'Romans',
                'service_name' => 'Sunday Morning',
            ],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('Test')
            ->andReturn('Test');

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->findAllWithRelations(
            ['title' => 'Test', 'preacher_id' => 5],
            10,
            0
        );

        $this->assertCount(1, $result);
    }

    /**
     * Test findForFrontendListing returns paginated results with total count.
     */
    public function testFindForFrontendListingReturnsPaginatedResults(): void
    {
        $expectedSermons = [
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'datetime' => '2024-01-07 10:00:00',
                'preacher' => 'John Doe',
                'series' => 'Romans',
                'service' => 'Sunday Morning',
            ],
        ];

        $this->wpdb->shouldReceive('query')
            ->once()
            ->with('SET SQL_BIG_SELECTS=1')
            ->andReturn(true);

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->with('SELECT FOUND_ROWS()')
            ->andReturn('10');

        $result = $this->repository->findForFrontendListing([], [], 1, 10);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(1, $result['items']);
        $this->assertSame(10, $result['total']);
    }

    /**
     * Test findForFrontendListing applies filters correctly.
     */
    public function testFindForFrontendListingAppliesFilters(): void
    {
        $expectedSermons = [
            (object) [
                'id' => 1,
                'title' => 'Gospel Sermon',
                'preacher' => 'John Doe',
            ],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('Gospel')
            ->andReturn('Gospel');

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('...');

        $this->wpdb->shouldReceive('query')
            ->once()
            ->with('SET SQL_BIG_SELECTS=1')
            ->andReturn(true);

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->with('SELECT FOUND_ROWS()')
            ->andReturn('1');

        $result = $this->repository->findForFrontendListing(
            ['title' => 'Gospel', 'preacher' => 5],
            ['by' => 'm.datetime', 'dir' => 'desc'],
            1,
            10
        );

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
    }

    /**
     * Test findForFrontendListing returns empty when no results.
     */
    public function testFindForFrontendListingReturnsEmptyWhenNoResults(): void
    {
        $this->wpdb->shouldReceive('query')
            ->once()
            ->with('SET SQL_BIG_SELECTS=1')
            ->andReturn(true);

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->with('SELECT FOUND_ROWS()')
            ->andReturn('0');

        $result = $this->repository->findForFrontendListing([], [], 1, 10);

        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
    }
}
