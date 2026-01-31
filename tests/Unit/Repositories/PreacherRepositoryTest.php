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
}
