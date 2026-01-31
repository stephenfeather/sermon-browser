<?php

/**
 * Tests for BookRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\BookRepository;
use Mockery;

/**
 * Test BookRepository functionality.
 */
class BookRepositoryTest extends TestCase
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
     * @var BookRepository
     */
    private BookRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new BookRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_books', $this->repository->getTableName());
    }

    /**
     * Test getBooksSermonTableName returns correct table name.
     */
    public function testGetBooksSermonTableName(): void
    {
        $this->assertSame('wp_sb_books_sermons', $this->repository->getBooksSermonTableName());
    }

    /**
     * Test findAllWithSermonCount returns books with counts.
     */
    public function testFindAllWithSermonCount(): void
    {
        $expectedBooks = [
            (object) ['name' => 'John', 'count' => 15],
            (object) ['name' => 'Romans', 'count' => 10],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedBooks);

        $result = $this->repository->findAllWithSermonCount();

        $this->assertCount(2, $result);
        $this->assertSame('John', $result[0]->name);
        $this->assertSame(15, $result[0]->count);
    }

    /**
     * Test findAllWithSermonCount returns empty array when no results.
     */
    public function testFindAllWithSermonCountReturnsEmptyArray(): void
    {
        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findAllWithSermonCount();

        $this->assertSame([], $result);
    }

    /**
     * Test findBySermonIdsWithCount returns books for specific sermon IDs.
     */
    public function testFindBySermonIdsWithCount(): void
    {
        $expectedBooks = [
            (object) ['name' => 'John', 'count' => 3],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedBooks);

        $result = $this->repository->findBySermonIdsWithCount([1, 2, 3]);

        $this->assertCount(1, $result);
        $this->assertSame('John', $result[0]->name);
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
        $expectedBooks = [
            (object) ['name' => 'Genesis', 'count' => 1],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedBooks);

        $result = $this->repository->findBySermonIdsWithCount([42]);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->count);
    }
}
