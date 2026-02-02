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
}
