<?php

/**
 * Tests for AbstractRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\PreacherRepository;
use Mockery;

/**
 * Test AbstractRepository functionality through PreacherRepository.
 */
class AbstractRepositoryTest extends TestCase
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
     * Test find returns entity when found.
     */
    public function testFindReturnsEntityWhenFound(): void
    {
        $expectedPreacher = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'description' => 'A preacher',
            'image' => '',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_preachers WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($expectedPreacher);

        $result = $this->repository->find(1);

        $this->assertSame($expectedPreacher, $result);
    }

    /**
     * Test find returns null when not found.
     */
    public function testFindReturnsNullWhenNotFound(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_preachers WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $result = $this->repository->find(999);

        $this->assertNull($result);
    }

    /**
     * Test findAll returns array of entities.
     */
    public function testFindAllReturnsArrayOfEntities(): void
    {
        $expectedPreachers = [
            (object) ['id' => 1, 'name' => 'John'],
            (object) ['id' => 2, 'name' => 'Jane'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findAll();

        $this->assertSame($expectedPreachers, $result);
    }

    /**
     * Test findAll with limit and offset.
     */
    public function testFindAllWithLimitAndOffset(): void
    {
        $expectedPreachers = [
            (object) ['id' => 2, 'name' => 'Jane'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 1)
            ->andReturn(' LIMIT 1 OFFSET 1');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedPreachers);

        $result = $this->repository->findAll([], 1, 1);

        $this->assertSame($expectedPreachers, $result);
    }

    /**
     * Test count returns correct count.
     */
    public function testCountReturnsCorrectCount(): void
    {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('5');

        $result = $this->repository->count();

        $this->assertSame(5, $result);
    }

    /**
     * Test count with criteria.
     */
    public function testCountWithCriteria(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'John')
            ->andReturn("name = 'John'");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1');

        $result = $this->repository->count(['name' => 'John']);

        $this->assertSame(1, $result);
    }

    /**
     * Test create returns new ID.
     */
    public function testCreateReturnsNewId(): void
    {
        $this->wpdb->insert_id = 5;

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sb_preachers',
                ['name' => 'New Preacher', 'description' => '', 'image' => '']
            )
            ->andReturn(1);

        $result = $this->repository->create([
            'name' => 'New Preacher',
            'description' => '',
            'image' => '',
        ]);

        $this->assertSame(5, $result);
    }

    /**
     * Test create filters out invalid columns.
     */
    public function testCreateFiltersInvalidColumns(): void
    {
        $this->wpdb->insert_id = 6;

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sb_preachers',
                ['name' => 'Test']
            )
            ->andReturn(1);

        $result = $this->repository->create([
            'name' => 'Test',
            'invalid_column' => 'should be filtered',
        ]);

        $this->assertSame(6, $result);
    }

    /**
     * Test update returns true on success.
     */
    public function testUpdateReturnsTrueOnSuccess(): void
    {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sb_preachers',
                ['name' => 'Updated Name'],
                ['id' => 1]
            )
            ->andReturn(1);

        $result = $this->repository->update(1, ['name' => 'Updated Name']);

        $this->assertTrue($result);
    }

    /**
     * Test update returns false on failure.
     */
    public function testUpdateReturnsFalseOnFailure(): void
    {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $result = $this->repository->update(1, ['name' => 'Updated']);

        $this->assertFalse($result);
    }

    /**
     * Test delete returns true on success.
     */
    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with(
                'wp_sb_preachers',
                ['id' => 1],
                ['%d']
            )
            ->andReturn(1);

        $result = $this->repository->delete(1);

        $this->assertTrue($result);
    }

    /**
     * Test delete returns false on failure.
     */
    public function testDeleteReturnsFalseOnFailure(): void
    {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    /**
     * Test exists returns true when entity exists.
     */
    public function testExistsReturnsTrueWhenExists(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_preachers WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn((object) ['id' => 1]);

        $result = $this->repository->exists(1);

        $this->assertTrue($result);
    }

    /**
     * Test exists returns false when entity does not exist.
     */
    public function testExistsReturnsFalseWhenNotExists(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sb_preachers WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $result = $this->repository->exists(999);

        $this->assertFalse($result);
    }
}
