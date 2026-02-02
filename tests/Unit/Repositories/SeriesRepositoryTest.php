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

    /**
     * Test findByName returns series when found.
     */
    public function testFindByNameReturnsSeries(): void
    {
        $expectedSeries = (object) ['id' => 1, 'name' => 'Romans'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'Romans')
            ->andReturn("name = 'Romans'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$expectedSeries]);

        $result = $this->repository->findByName('Romans');

        $this->assertSame('Romans', $result->name);
    }

    /**
     * Test findByName returns null when not found.
     */
    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'NonExistent')
            ->andReturn("name = 'NonExistent'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findByName('NonExistent');

        $this->assertNull($result);
    }

    /**
     * Test searchByName returns matching series.
     */
    public function testSearchByNameReturnsMatchingSeries(): void
    {
        $expectedSeries = [
            (object) ['id' => 1, 'name' => 'Romans'],
            (object) ['id' => 2, 'name' => 'Romans Part 2'],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('Romans')
            ->andReturn('Romans');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_series WHERE name LIKE '%Romans%' ORDER BY name ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->searchByName('Romans');

        $this->assertCount(2, $result);
        $this->assertStringContainsString('Romans', $result[0]->name);
    }

    /**
     * Test searchByName returns empty array when no matches.
     */
    public function testSearchByNameReturnsEmptyArrayWhenNoMatches(): void
    {
        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('xyz')
            ->andReturn('xyz');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_series WHERE name LIKE '%xyz%' ORDER BY name ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->searchByName('xyz');

        $this->assertSame([], $result);
    }

    /**
     * Test findAllSorted returns series sorted by name.
     */
    public function testFindAllSortedReturnsSortedSeries(): void
    {
        $expectedSeries = [
            (object) ['id' => 2, 'name' => 'Acts'],
            (object) ['id' => 1, 'name' => 'Genesis'],
            (object) ['id' => 3, 'name' => 'Romans'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findAllSorted();

        $this->assertCount(3, $result);
        $this->assertSame('Acts', $result[0]->name);
    }

    /**
     * Test findAllWithSermonCount returns series with counts.
     */
    public function testFindAllWithSermonCountReturnsSeriesWithCounts(): void
    {
        $expectedSeries = [
            (object) ['id' => 1, 'name' => 'Romans', 'sermon_count' => 15],
            (object) ['id' => 2, 'name' => 'Genesis', 'sermon_count' => 8],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findAllWithSermonCount();

        $this->assertCount(2, $result);
        $this->assertSame(15, $result[0]->sermon_count);
        $this->assertSame(8, $result[1]->sermon_count);
    }

    /**
     * Test hasSermons returns true when series has sermons.
     */
    public function testHasSermonsReturnsTrueWhenHasSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_sermons WHERE series_id = 5");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('10');

        $result = $this->repository->hasSermons(5);

        $this->assertTrue($result);
    }

    /**
     * Test hasSermons returns false when series has no sermons.
     */
    public function testHasSermonsReturnsFalseWhenNoSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_sermons WHERE series_id = 999");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $result = $this->repository->hasSermons(999);

        $this->assertFalse($result);
    }

    /**
     * Test findWithPages returns series with linked pages.
     */
    public function testFindWithPagesReturnsSeriesWithPages(): void
    {
        $expectedSeries = [
            (object) ['id' => 1, 'name' => 'Romans', 'page_id' => 42],
            (object) ['id' => 2, 'name' => 'Genesis', 'page_id' => 56],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findWithPages();

        $this->assertCount(2, $result);
        $this->assertSame(42, $result[0]->page_id);
    }

    /**
     * Test findWithPages returns empty array when none have pages.
     */
    public function testFindWithPagesReturnsEmptyArrayWhenNoneHavePages(): void
    {
        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findWithPages();

        $this->assertSame([], $result);
    }

    /**
     * Test findByNameLike returns series with case-insensitive match.
     */
    public function testFindByNameLikeReturnsMatchingSeries(): void
    {
        $expectedSeries = (object) ['id' => 1, 'name' => 'Romans'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_series WHERE name LIKE 'romans' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($expectedSeries);

        $result = $this->repository->findByNameLike('romans');

        $this->assertSame('Romans', $result->name);
    }

    /**
     * Test findByNameLike returns null for empty string.
     */
    public function testFindByNameLikeReturnsNullForEmptyString(): void
    {
        $result = $this->repository->findByNameLike('');

        $this->assertNull($result);
    }

    /**
     * Test findByNameLike returns null when not found.
     */
    public function testFindByNameLikeReturnsNullWhenNotFound(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_series WHERE name LIKE 'xyz' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $result = $this->repository->findByNameLike('xyz');

        $this->assertNull($result);
    }

    /**
     * Test findOrCreate returns existing series ID.
     */
    public function testFindOrCreateReturnsExistingId(): void
    {
        $existingSeries = (object) ['id' => 5, 'name' => 'Romans'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_series WHERE name LIKE 'Romans' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($existingSeries);

        $result = $this->repository->findOrCreate('Romans');

        $this->assertSame(5, $result);
    }

    /**
     * Test findOrCreate creates new series when not found.
     */
    public function testFindOrCreateCreatesNewSeries(): void
    {
        $this->wpdb->insert_id = 10;

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_series WHERE name LIKE 'New Series' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sb_series',
                ['name' => 'New Series', 'page_id' => 0]
            )
            ->andReturn(1);

        $result = $this->repository->findOrCreate('New Series');

        $this->assertSame(10, $result);
    }

    /**
     * Test findOrCreate returns 0 for empty name.
     */
    public function testFindOrCreateReturnsZeroForEmptyName(): void
    {
        $result = $this->repository->findOrCreate('');

        $this->assertSame(0, $result);
    }
}
