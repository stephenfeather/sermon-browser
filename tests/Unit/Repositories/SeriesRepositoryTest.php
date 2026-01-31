<?php

/**
 * Tests for SeriesRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\SeriesRepository;
use Mockery;

/**
 * Test SeriesRepository functionality.
 */
class SeriesRepositoryTest extends TestCase
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
     * @var SeriesRepository
     */
    private SeriesRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new SeriesRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_series', $this->repository->getTableName());
    }

    /**
     * Test findAllForFilter returns series with counts ordered by datetime DESC.
     */
    public function testFindAllForFilter(): void
    {
        $expectedSeries = [
            (object) ['id' => 1, 'name' => 'Romans', 'count' => 12],
            (object) ['id' => 2, 'name' => 'Genesis', 'count' => 8],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findAllForFilter();

        $this->assertCount(2, $result);
        $this->assertSame('Romans', $result[0]->name);
        $this->assertSame(12, $result[0]->count);
    }

    /**
     * Test findAllForFilter returns empty array when no results.
     */
    public function testFindAllForFilterReturnsEmptyArray(): void
    {
        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findAllForFilter();

        $this->assertSame([], $result);
    }

    /**
     * Test findBySermonIdsWithCount returns series for specific sermon IDs.
     */
    public function testFindBySermonIdsWithCount(): void
    {
        $expectedSeries = [
            (object) ['id' => 1, 'name' => 'Romans', 'count' => 3],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findBySermonIdsWithCount([1, 2, 3]);

        $this->assertCount(1, $result);
        $this->assertSame('Romans', $result[0]->name);
        $this->assertSame(3, $result[0]->count);
    }

    /**
     * Test findBySermonIdsWithCount returns empty array for empty input.
     */
    public function testFindBySermonIdsWithCountReturnsEmptyForEmptyInput(): void
    {
        $result = $this->repository->findBySermonIdsWithCount([]);

        $this->assertSame([], $result);
    }

    /**
     * Test findBySermonIdsWithCount handles single sermon ID.
     */
    public function testFindBySermonIdsWithCountSingleId(): void
    {
        $expectedSeries = [
            (object) ['id' => 5, 'name' => 'Acts', 'count' => 1],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findBySermonIdsWithCount([42]);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->count);
    }
}
