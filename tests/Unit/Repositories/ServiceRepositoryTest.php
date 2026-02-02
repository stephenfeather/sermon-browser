<?php

/**
 * Tests for ServiceRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\ServiceRepository;
use Mockery;

/**
 * Test ServiceRepository functionality.
 */
class ServiceRepositoryTest extends TestCase
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
     * @var ServiceRepository
     */
    private ServiceRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new ServiceRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_services', $this->repository->getTableName());
    }

    /**
     * Test findAllForFilter returns services with counts ordered by count DESC.
     */
    public function testFindAllForFilter(): void
    {
        $expectedServices = [
            (object) ['id' => 1, 'name' => 'Sunday Morning', 'count' => 50],
            (object) ['id' => 2, 'name' => 'Wednesday Evening', 'count' => 20],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedServices);

        $result = $this->repository->findAllForFilter();

        $this->assertCount(2, $result);
        $this->assertSame('Sunday Morning', $result[0]->name);
        $this->assertSame(50, $result[0]->count);
        $this->assertSame(20, $result[1]->count);
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
     * Test findBySermonIdsWithCount returns services for specific sermon IDs.
     */
    public function testFindBySermonIdsWithCount(): void
    {
        $expectedServices = [
            (object) ['id' => 1, 'name' => 'Sunday Morning', 'count' => 3],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedServices);

        $result = $this->repository->findBySermonIdsWithCount([1, 2, 3]);

        $this->assertCount(1, $result);
        $this->assertSame('Sunday Morning', $result[0]->name);
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
        $expectedServices = [
            (object) ['id' => 5, 'name' => 'Special Service', 'count' => 1],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedServices);

        $result = $this->repository->findBySermonIdsWithCount([42]);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->count);
    }

    /**
     * Test findByName returns service when found.
     */
    public function testFindByNameReturnsService(): void
    {
        $expectedService = (object) ['id' => 1, 'name' => 'Sunday Morning'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'Sunday Morning')
            ->andReturn("name = 'Sunday Morning'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$expectedService]);

        $result = $this->repository->findByName('Sunday Morning');

        $this->assertSame('Sunday Morning', $result->name);
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
     * Test findAllSorted returns services sorted by name.
     */
    public function testFindAllSortedReturnsSortedServices(): void
    {
        $expectedServices = [
            (object) ['id' => 2, 'name' => 'Sunday Evening'],
            (object) ['id' => 1, 'name' => 'Sunday Morning'],
            (object) ['id' => 3, 'name' => 'Wednesday Evening'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedServices);

        $result = $this->repository->findAllSorted();

        $this->assertCount(3, $result);
        $this->assertSame('Sunday Evening', $result[0]->name);
    }

    /**
     * Test findAllByTime returns services sorted by time.
     */
    public function testFindAllByTimeReturnsSortedByTime(): void
    {
        $expectedServices = [
            (object) ['id' => 1, 'name' => 'Sunday Morning', 'time' => '09:00'],
            (object) ['id' => 2, 'name' => 'Sunday Evening', 'time' => '18:00'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedServices);

        $result = $this->repository->findAllByTime();

        $this->assertCount(2, $result);
        $this->assertSame('09:00', $result[0]->time);
    }

    /**
     * Test findAllWithSermonCount returns services with counts.
     */
    public function testFindAllWithSermonCountReturnsServicesWithCounts(): void
    {
        $expectedServices = [
            (object) ['id' => 1, 'name' => 'Sunday Morning', 'sermon_count' => 52],
            (object) ['id' => 2, 'name' => 'Wednesday Evening', 'sermon_count' => 26],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedServices);

        $result = $this->repository->findAllWithSermonCount();

        $this->assertCount(2, $result);
        $this->assertSame(52, $result[0]->sermon_count);
        $this->assertSame(26, $result[1]->sermon_count);
    }

    /**
     * Test hasSermons returns true when service has sermons.
     */
    public function testHasSermonsReturnsTrueWhenHasSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_sermons WHERE service_id = 5");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('10');

        $result = $this->repository->hasSermons(5);

        $this->assertTrue($result);
    }

    /**
     * Test hasSermons returns false when service has no sermons.
     */
    public function testHasSermonsReturnsFalseWhenNoSermons(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_sermons WHERE service_id = 999");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $result = $this->repository->hasSermons(999);

        $this->assertFalse($result);
    }

    /**
     * Test updateWithTimeShift updates service and cascades time change.
     */
    public function testUpdateWithTimeShiftUpdatesServiceAndCascades(): void
    {
        // Mock getting old time
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT time FROM wp_sb_services WHERE id = 1");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('09:00');

        // Mock the update
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sb_services',
                ['name' => 'Sunday Morning', 'time' => '10:00'],
                ['id' => 1],
                ['%s', '%s'],
                ['%d']
            )
            ->andReturn(1);

        // Mock the cascade update
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE wp_sb_sermons...");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(5);

        $result = $this->repository->updateWithTimeShift(1, 'Sunday Morning', '10:00');

        $this->assertTrue($result);
    }

    /**
     * Test updateWithTimeShift returns false when update fails.
     */
    public function testUpdateWithTimeShiftReturnsFalseOnFailure(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT time FROM wp_sb_services WHERE id = 1");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('09:00');

        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $result = $this->repository->updateWithTimeShift(1, 'Sunday Morning', '10:00');

        $this->assertFalse($result);
    }

    /**
     * Test updateWithTimeShift skips cascade when time unchanged.
     */
    public function testUpdateWithTimeShiftSkipsCascadeWhenTimeUnchanged(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT time FROM wp_sb_services WHERE id = 1");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('09:00');

        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(1);

        // No cascade query should happen when time is same
        $result = $this->repository->updateWithTimeShift(1, 'Sunday Morning Updated', '09:00');

        $this->assertTrue($result);
    }

    /**
     * Test getTime returns time for service.
     */
    public function testGetTimeReturnsTime(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT time FROM wp_sb_services WHERE id = 1");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('09:00');

        $result = $this->repository->getTime(1);

        $this->assertSame('09:00', $result);
    }

    /**
     * Test getTime returns null when service not found.
     */
    public function testGetTimeReturnsNullWhenNotFound(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT time FROM wp_sb_services WHERE id = 999");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $result = $this->repository->getTime(999);

        $this->assertNull($result);
    }
}
