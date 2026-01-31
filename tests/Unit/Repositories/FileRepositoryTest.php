<?php

/**
 * Tests for FileRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\FileRepository;
use Mockery;

/**
 * Test FileRepository functionality.
 */
class FileRepositoryTest extends TestCase
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
     * @var FileRepository
     */
    private FileRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new FileRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_stuff', $this->repository->getTableName());
    }

    /**
     * Test getPopularSermons returns sermons ordered by download count.
     */
    public function testGetPopularSermons(): void
    {
        $expectedSermons = [
            (object) ['id' => 5, 'title' => 'Most Popular', 'total' => 100],
            (object) ['id' => 3, 'title' => 'Second Popular', 'total' => 75],
            (object) ['id' => 8, 'title' => 'Third Popular', 'total' => 50],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedSermons);

        $result = $this->repository->getPopularSermons(3);

        $this->assertCount(3, $result);
        $this->assertSame('Most Popular', $result[0]->title);
        $this->assertSame(100, $result[0]->total);
    }

    /**
     * Test getPopularSermons returns empty array when no sermons.
     */
    public function testGetPopularSermonsReturnsEmptyArray(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->getPopularSermons(5);

        $this->assertSame([], $result);
    }

    /**
     * Test getPopularSeries returns series with combined ranking.
     */
    public function testGetPopularSeries(): void
    {
        // First query: series by average downloads
        $byAverage = [
            (object) ['id' => 2, 'name' => 'Romans', 'average' => 50.0],
            (object) ['id' => 1, 'name' => 'Genesis', 'average' => 40.0],
            (object) ['id' => 3, 'name' => 'Psalms', 'average' => 30.0],
        ];

        // Second query: series by total downloads
        $byTotal = [
            (object) ['id' => 1, 'name' => 'Genesis', 'total' => 200],
            (object) ['id' => 2, 'name' => 'Romans', 'total' => 150],
            (object) ['id' => 3, 'name' => 'Psalms', 'total' => 100],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->twice()
            ->andReturn($byAverage, $byTotal);

        $result = $this->repository->getPopularSeries(2);

        $this->assertCount(2, $result);
        // Combined ranking: Romans (1+2=3), Genesis (2+1=3), Psalms (3+3=6)
        // Tiebreaker goes to first encountered, so Romans or Genesis first
        $names = array_map(fn($s) => $s->name, $result);
        $this->assertContains('Romans', $names);
        $this->assertContains('Genesis', $names);
    }

    /**
     * Test getPopularSeries returns empty array when no series.
     */
    public function testGetPopularSeriesReturnsEmptyArray(): void
    {
        $this->wpdb->shouldReceive('get_results')
            ->twice()
            ->andReturn([]);

        $result = $this->repository->getPopularSeries(5);

        $this->assertSame([], $result);
    }

    /**
     * Test getPopularPreachers returns preachers with combined ranking.
     */
    public function testGetPopularPreachers(): void
    {
        // First query: preachers by average downloads
        $byAverage = [
            (object) ['id' => 1, 'name' => 'John Smith', 'average' => 60.0],
            (object) ['id' => 2, 'name' => 'Jane Doe', 'average' => 45.0],
        ];

        // Second query: preachers by total downloads
        $byTotal = [
            (object) ['id' => 2, 'name' => 'Jane Doe', 'total' => 300],
            (object) ['id' => 1, 'name' => 'John Smith', 'total' => 250],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->twice()
            ->andReturn($byAverage, $byTotal);

        $result = $this->repository->getPopularPreachers(2);

        $this->assertCount(2, $result);
        // Combined ranking: John (1+2=3), Jane (2+1=3) - tie
        $names = array_map(fn($p) => $p->name, $result);
        $this->assertContains('John Smith', $names);
        $this->assertContains('Jane Doe', $names);
    }

    /**
     * Test getPopularPreachers returns empty array when no preachers.
     */
    public function testGetPopularPreachersReturnsEmptyArray(): void
    {
        $this->wpdb->shouldReceive('get_results')
            ->twice()
            ->andReturn([]);

        $result = $this->repository->getPopularPreachers(5);

        $this->assertSame([], $result);
    }

    /**
     * Test getPopularSeries excludes null series IDs.
     */
    public function testGetPopularSeriesExcludesNullIds(): void
    {
        // First query includes null ID
        $byAverage = [
            (object) ['id' => null, 'name' => null, 'average' => 100.0],
            (object) ['id' => 1, 'name' => 'Genesis', 'average' => 50.0],
        ];

        // Second query also includes null
        $byTotal = [
            (object) ['id' => null, 'name' => null, 'total' => 500],
            (object) ['id' => 1, 'name' => 'Genesis', 'total' => 200],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->twice()
            ->andReturn($byAverage, $byTotal);

        $result = $this->repository->getPopularSeries(5);

        $this->assertCount(1, $result);
        $this->assertSame('Genesis', $result[0]->name);
    }
}
