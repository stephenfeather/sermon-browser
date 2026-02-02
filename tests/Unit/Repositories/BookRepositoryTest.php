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

    /**
     * Test truncate clears the books table.
     */
    public function testTruncateClearsTable(): void
    {
        $this->wpdb->shouldReceive('query')
            ->once()
            ->with('TRUNCATE TABLE wp_sb_books')
            ->andReturn(true);

        $result = $this->repository->truncate();

        $this->assertTrue($result);
    }

    /**
     * Test truncate returns false on failure.
     */
    public function testTruncateReturnsFalseOnFailure(): void
    {
        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(false);

        $result = $this->repository->truncate();

        $this->assertFalse($result);
    }

    /**
     * Test insertBook inserts a book and returns insert ID.
     */
    public function testInsertBookReturnsInsertId(): void
    {
        $this->wpdb->insert_id = 5;

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("INSERT INTO wp_sb_books VALUES (null, 'Genesis')");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(1);

        $result = $this->repository->insertBook('Genesis');

        $this->assertSame(5, $result);
    }

    /**
     * Test updateBookNameInSermons updates book name in pivot table.
     */
    public function testUpdateBookNameInSermonsUpdatesNames(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE wp_sb_books_sermons SET book_name = 'Genèse' WHERE book_name = 'Genesis'");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(10);

        $result = $this->repository->updateBookNameInSermons('Genèse', 'Genesis');

        $this->assertTrue($result);
    }

    /**
     * Test deleteBySermonId removes all passage references for a sermon.
     */
    public function testDeleteBySermonIdRemovesPassageReferences(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("DELETE FROM wp_sb_books_sermons WHERE sermon_id = 42");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(3);

        $result = $this->repository->deleteBySermonId(42);

        $this->assertTrue($result);
    }

    /**
     * Test insertPassageRef inserts a passage reference.
     */
    public function testInsertPassageRefReturnsInsertId(): void
    {
        $this->wpdb->insert_id = 10;

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("INSERT INTO wp_sb_books_sermons...");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(1);

        $result = $this->repository->insertPassageRef(
            'John',
            '3',
            '16',
            0,
            'start',
            42
        );

        $this->assertSame(10, $result);
    }

    /**
     * Test findBySermonId returns passage references for sermon.
     */
    public function testFindBySermonIdReturnsPassageReferences(): void
    {
        $expectedRefs = [
            (object) ['id' => 1, 'book_name' => 'John', 'chapter' => '3', 'verse' => '16', 'order' => 0, 'type' => 'start'],
            (object) ['id' => 2, 'book_name' => 'John', 'chapter' => '3', 'verse' => '21', 'order' => 0, 'type' => 'end'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_books_sermons WHERE sermon_id = 42 ORDER BY `order` ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedRefs);

        $result = $this->repository->findBySermonId(42);

        $this->assertCount(2, $result);
        $this->assertSame('John', $result[0]->book_name);
        $this->assertSame('start', $result[0]->type);
    }

    /**
     * Test findBySermonId returns empty array when no references.
     */
    public function testFindBySermonIdReturnsEmptyArrayWhenNoReferences(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_books_sermons WHERE sermon_id = 999 ORDER BY `order` ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findBySermonId(999);

        $this->assertSame([], $result);
    }

    /**
     * Test findAllNames returns book names in order.
     */
    public function testFindAllNamesReturnsBookNames(): void
    {
        $expectedNames = ['Genesis', 'Exodus', 'Leviticus'];

        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn($expectedNames);

        $result = $this->repository->findAllNames();

        $this->assertCount(3, $result);
        $this->assertSame('Genesis', $result[0]);
    }

    /**
     * Test findAllNames returns empty array when no books.
     */
    public function testFindAllNamesReturnsEmptyArrayWhenNoBooks(): void
    {
        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn([]);

        $result = $this->repository->findAllNames();

        $this->assertSame([], $result);
    }

    /**
     * Test getSermonsWithVerseData returns sermons with verse data.
     */
    public function testGetSermonsWithVerseDataReturnsSermons(): void
    {
        $expectedSermons = [
            (object) ['id' => 1, 'start' => 'a:1:{}', 'end' => 'a:1:{}'],
            (object) ['id' => 2, 'start' => 'a:1:{}', 'end' => 'a:1:{}'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->getSermonsWithVerseData();

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
    }

    /**
     * Test updateSermonVerseData updates sermon verse data.
     */
    public function testUpdateSermonVerseDataUpdatesData(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE wp_sb_sermons SET start = '...', end = '...' WHERE id = 42");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(1);

        $result = $this->repository->updateSermonVerseData(42, 'a:1:{}', 'a:1:{}');

        $this->assertTrue($result);
    }
}
