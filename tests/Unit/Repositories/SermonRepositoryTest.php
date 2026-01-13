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
     * Test findPrevious returns previous sermon.
     */
    public function testFindPrevious(): void
    {
        $currentSermon = (object) [
            'id' => 5,
            'datetime' => '2024-01-15 10:00:00',
        ];

        $previousSermon = (object) [
            'id' => 4,
            'datetime' => '2024-01-08 10:00:00',
        ];

        // First call to find current sermon
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->twice()
            ->andReturn($currentSermon, $previousSermon);

        $result = $this->repository->findPrevious(5);

        $this->assertSame(4, $result->id);
    }

    /**
     * Test findNext returns next sermon.
     */
    public function testFindNext(): void
    {
        $currentSermon = (object) [
            'id' => 5,
            'datetime' => '2024-01-15 10:00:00',
        ];

        $nextSermon = (object) [
            'id' => 6,
            'datetime' => '2024-01-22 10:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_row')
            ->twice()
            ->andReturn($currentSermon, $nextSermon);

        $result = $this->repository->findNext(5);

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
}
