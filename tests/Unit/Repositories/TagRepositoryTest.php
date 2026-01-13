<?php
/**
 * Tests for TagRepository.
 *
 * @package SermonBrowser\Tests\Unit\Repositories
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Repositories;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Repositories\TagRepository;
use Mockery;

/**
 * Test TagRepository functionality including pivot table operations.
 */
class TagRepositoryTest extends TestCase
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
     * @var TagRepository
     */
    private TagRepository $repository;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';

        $this->repository = new TagRepository($this->wpdb);
    }

    /**
     * Test getTableName returns correct table name.
     */
    public function testGetTableName(): void
    {
        $this->assertSame('wp_sb_tags', $this->repository->getTableName());
    }

    /**
     * Test findByName returns tag when found.
     */
    public function testFindByName(): void
    {
        $expectedTag = (object) ['id' => 1, 'name' => 'Faith'];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'Faith')
            ->andReturn("name = 'Faith'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$expectedTag]);

        $result = $this->repository->findByName('Faith');

        $this->assertSame('Faith', $result->name);
    }

    /**
     * Test findOrCreate returns existing tag ID.
     */
    public function testFindOrCreateReturnsExistingId(): void
    {
        $existingTag = (object) ['id' => 5, 'name' => 'Grace'];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn("name = 'Grace'", ' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$existingTag]);

        $result = $this->repository->findOrCreate('Grace');

        $this->assertSame(5, $result);
    }

    /**
     * Test findOrCreate creates new tag when not found.
     */
    public function testFindOrCreateCreatesNewTag(): void
    {
        $this->wpdb->insert_id = 10;

        $this->wpdb->shouldReceive('prepare')
            ->andReturn("name = 'NewTag'", ' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with('wp_sb_tags', ['name' => 'NewTag'])
            ->andReturn(1);

        $result = $this->repository->findOrCreate('NewTag');

        $this->assertSame(10, $result);
    }

    /**
     * Test findBySermon returns tags for a sermon.
     */
    public function testFindBySermon(): void
    {
        $expectedTags = [
            (object) ['id' => 1, 'name' => 'Faith'],
            (object) ['id' => 2, 'name' => 'Hope'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedTags);

        $result = $this->repository->findBySermon(1);

        $this->assertCount(2, $result);
        $this->assertSame('Faith', $result[0]->name);
    }

    /**
     * Test getTagNamesForSermon returns comma-separated names.
     */
    public function testGetTagNamesForSermon(): void
    {
        $tags = [
            (object) ['id' => 1, 'name' => 'Faith'],
            (object) ['id' => 2, 'name' => 'Hope'],
            (object) ['id' => 3, 'name' => 'Love'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($tags);

        $result = $this->repository->getTagNamesForSermon(1);

        $this->assertSame('Faith, Hope, Love', $result);
    }

    /**
     * Test attachToSermon adds tag to sermon.
     */
    public function testAttachToSermon(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id FROM wp_sb_sermons_tags...');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null); // Not already attached

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sb_sermons_tags',
                ['sermon_id' => 1, 'tag_id' => 5],
                ['%d', '%d']
            )
            ->andReturn(1);

        $result = $this->repository->attachToSermon(1, 5);

        $this->assertTrue($result);
    }

    /**
     * Test attachToSermon returns true if already attached.
     */
    public function testAttachToSermonReturnsTrueIfAlreadyAttached(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id FROM wp_sb_sermons_tags...');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('123'); // Already attached

        $result = $this->repository->attachToSermon(1, 5);

        $this->assertTrue($result);
    }

    /**
     * Test detachFromSermon removes tag from sermon.
     */
    public function testDetachFromSermon(): void
    {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with(
                'wp_sb_sermons_tags',
                ['sermon_id' => 1, 'tag_id' => 5],
                ['%d', '%d']
            )
            ->andReturn(1);

        $result = $this->repository->detachFromSermon(1, 5);

        $this->assertTrue($result);
    }

    /**
     * Test detachAllFromSermon removes all tags from sermon.
     */
    public function testDetachAllFromSermon(): void
    {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with(
                'wp_sb_sermons_tags',
                ['sermon_id' => 1],
                ['%d']
            )
            ->andReturn(5);

        $result = $this->repository->detachAllFromSermon(1);

        $this->assertTrue($result);
    }

    /**
     * Test syncSermonTags removes old and adds new tags.
     */
    public function testSyncSermonTags(): void
    {
        // First, detach all
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_sb_sermons_tags', ['sermon_id' => 1], ['%d'])
            ->andReturn(2);

        // Then attach new tags (two tags: 3 and 5)
        $this->wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn('SELECT...');

        $this->wpdb->shouldReceive('get_var')
            ->twice()
            ->andReturn(null); // Neither tag attached

        $this->wpdb->shouldReceive('insert')
            ->twice()
            ->andReturn(1);

        $result = $this->repository->syncSermonTags(1, [3, 5]);

        $this->assertTrue($result);
    }

    /**
     * Test findAllWithSermonCount returns tags with counts.
     */
    public function testFindAllWithSermonCount(): void
    {
        $expectedTags = [
            (object) ['id' => 1, 'name' => 'Faith', 'sermon_count' => 10],
            (object) ['id' => 2, 'name' => 'Hope', 'sermon_count' => 5],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedTags);

        $result = $this->repository->findAllWithSermonCount();

        $this->assertCount(2, $result);
        $this->assertSame(10, $result[0]->sermon_count);
    }

    /**
     * Test getSermonIdsByTag returns sermon IDs.
     */
    public function testGetSermonIdsByTag(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT sermon_id FROM wp_sb_sermons_tags WHERE tag_id = 5');

        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn(['1', '3', '7']);

        $result = $this->repository->getSermonIdsByTag(5);

        $this->assertSame([1, 3, 7], $result);
    }

    /**
     * Test deleteUnused removes orphaned tags.
     */
    public function testDeleteUnused(): void
    {
        $this->wpdb->rows_affected = 3;

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(3);

        $result = $this->repository->deleteUnused();

        $this->assertSame(3, $result);
    }
}
