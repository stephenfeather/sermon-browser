<?php

/**
 * Tests for PreacherRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\PreacherRepository;
use Mockery;

/**
 * Test PreacherRepository functionality.
 */
class PreacherRepositoryTest extends TestCase
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
     * @var PreacherRepository
     */
    private PreacherRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new PreacherRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_preachers', $this->repository->getTableName());
    }

    /**
     * Test findAllForFilter returns preachers with counts ordered by count DESC.
     */
    public function testFindAllForFilter(): void
    {
        $expectedPreachers = [
            (object) ['id' => 1, 'name' => 'John Doe', 'count' => 10],
            (object) ['id' => 2, 'name' => 'Jane Smith', 'count' => 5],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findAllForFilter();

        $this->assertCount(2, $result);
        $this->assertSame('John Doe', $result[0]->name);
        $this->assertSame(10, $result[0]->count);
        $this->assertSame(5, $result[1]->count);
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
     * Test findBySermonIdsWithCount returns preachers for specific sermon IDs.
     */
    public function testFindBySermonIdsWithCount(): void
    {
        $expectedPreachers = [
            (object) ['id' => 1, 'name' => 'John Doe', 'count' => 3],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findBySermonIdsWithCount([1, 2, 3]);

        $this->assertCount(1, $result);
        $this->assertSame('John Doe', $result[0]->name);
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
        $expectedPreachers = [
            (object) ['id' => 5, 'name' => 'Pastor Mike', 'count' => 1],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findBySermonIdsWithCount([42]);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->count);
    }

    /**
     * Test findByName returns preacher when found.
     */
    public function testFindByNameReturnsPreacher(): void
    {
        $expectedPreacher = (object) ['id' => 1, 'name' => 'John Doe'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'John Doe')
            ->andReturn("name = 'John Doe'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$expectedPreacher]);

        $result = $this->repository->findByName('John Doe');

        $this->assertSame('John Doe', $result->name);
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
     * Test searchByName returns matching preachers.
     */
    public function testSearchByNameReturnsMatchingPreachers(): void
    {
        $expectedPreachers = [
            (object) ['id' => 1, 'name' => 'John Doe'],
            (object) ['id' => 2, 'name' => 'Johnny Smith'],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('John')
            ->andReturn('John');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_preachers WHERE name LIKE '%John%' ORDER BY name ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->searchByName('John');

        $this->assertCount(2, $result);
        $this->assertStringContainsString('John', $result[0]->name);
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
            ->andReturn("SELECT * FROM wp_sb_preachers WHERE name LIKE '%xyz%' ORDER BY name ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->searchByName('xyz');

        $this->assertSame([], $result);
    }

    /**
     * Test findAllSorted returns preachers sorted by name.
     */
    public function testFindAllSortedReturnsSortedPreachers(): void
    {
        $expectedPreachers = [
            (object) ['id' => 2, 'name' => 'Alice'],
            (object) ['id' => 1, 'name' => 'Bob'],
            (object) ['id' => 3, 'name' => 'Charlie'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findAllSorted();

        $this->assertCount(3, $result);
        $this->assertSame('Alice', $result[0]->name);
    }

    /**
     * Test findAllWithSermonCount returns preachers with counts.
     */
    public function testFindAllWithSermonCountReturnsPreachersWithCounts(): void
    {
        $expectedPreachers = [
            (object) ['id' => 1, 'name' => 'John Doe', 'sermon_count' => 15],
            (object) ['id' => 2, 'name' => 'Jane Smith', 'sermon_count' => 8],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findAllWithSermonCount();

        $this->assertCount(2, $result);
        $this->assertSame(15, $result[0]->sermon_count);
        $this->assertSame(8, $result[1]->sermon_count);
    }

    /**
     * Test hasSermons returns true when preacher has sermons.
     */
    public function testHasSermonsReturnsTrueWhenHasSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_sermons WHERE preacher_id = 5");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('10');

        $result = $this->repository->hasSermons(5);

        $this->assertTrue($result);
    }

    /**
     * Test hasSermons returns false when preacher has no sermons.
     */
    public function testHasSermonsReturnsFalseWhenNoSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_sermons WHERE preacher_id = 999");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $result = $this->repository->hasSermons(999);

        $this->assertFalse($result);
    }

    /**
     * Test findByNameLike returns preacher with case-insensitive match.
     */
    public function testFindByNameLikeReturnsMatchingPreacher(): void
    {
        $expectedPreacher = (object) ['id' => 1, 'name' => 'John Doe'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_preachers WHERE name LIKE 'john doe' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($expectedPreacher);

        $result = $this->repository->findByNameLike('john doe');

        $this->assertSame('John Doe', $result->name);
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
            ->andReturn("SELECT * FROM wp_sb_preachers WHERE name LIKE 'xyz' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $result = $this->repository->findByNameLike('xyz');

        $this->assertNull($result);
    }

    /**
     * Test findOrCreate returns existing preacher ID.
     */
    public function testFindOrCreateReturnsExistingId(): void
    {
        $existingPreacher = (object) ['id' => 5, 'name' => 'John Doe'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_preachers WHERE name LIKE 'John Doe' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($existingPreacher);

        $result = $this->repository->findOrCreate('John Doe');

        $this->assertSame(5, $result);
    }

    /**
     * Test findOrCreate creates new preacher when not found.
     */
    public function testFindOrCreateCreatesNewPreacher(): void
    {
        $this->wpdb->insert_id = 10;

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_preachers WHERE name LIKE 'New Preacher' LIMIT 1");

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sb_preachers',
                ['name' => 'New Preacher', 'description' => '', 'image' => '']
            )
            ->andReturn(1);

        $result = $this->repository->findOrCreate('New Preacher');

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
