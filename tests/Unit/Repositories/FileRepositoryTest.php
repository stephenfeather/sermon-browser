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
     * Test findBySermon returns files for a sermon.
     */
    public function testFindBySermonReturnsFilesForSermon(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'sermon.mp3', 'type' => 'file'],
            (object) ['id' => 2, 'name' => 'notes.pdf', 'type' => 'file'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('sermon_id = %d', 42)
            ->andReturn('sermon_id = 42');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findBySermon(42);

        $this->assertCount(2, $result);
        $this->assertSame('sermon.mp3', $result[0]->name);
    }

    /**
     * Test findBySermonAndType returns files matching both criteria.
     */
    public function testFindBySermonAndTypeReturnsMatchingFiles(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'sermon.mp3', 'type' => 'file'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('sermon_id = %d', 42)
            ->andReturn('sermon_id = 42');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('type = %s', 'file')
            ->andReturn("type = 'file'");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findBySermonAndType(42, 'file');

        $this->assertCount(1, $result);
    }

    /**
     * Test findByType returns files of a specific type.
     */
    public function testFindByTypeReturnsFilesOfType(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'sermon1.mp3', 'type' => 'file'],
            (object) ['id' => 2, 'name' => 'sermon2.mp3', 'type' => 'file'],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('type = %s', 'file')
            ->andReturn("type = 'file'");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findByType('file');

        $this->assertCount(2, $result);
    }

    /**
     * Test incrementCountByName increments download count.
     */
    public function testIncrementCountByNameIncrementsCount(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE wp_sb_stuff SET count = count + 1 WHERE name = 'sermon.mp3'");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(1);

        $result = $this->repository->incrementCountByName('sermon.mp3');

        $this->assertTrue($result);
    }

    /**
     * Test incrementCountByName returns false on failure.
     */
    public function testIncrementCountByNameReturnsFalseOnFailure(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE...");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(false);

        $result = $this->repository->incrementCountByName('nonexistent.mp3');

        $this->assertFalse($result);
    }

    /**
     * Test findUnlinked returns unlinked files.
     */
    public function testFindUnlinkedReturnsUnlinkedFiles(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'orphan.mp3', 'sermon_id' => 0],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findUnlinked();

        $this->assertCount(1, $result);
        $this->assertSame('orphan.mp3', $result[0]->name);
    }

    /**
     * Test findUnlinked with limit.
     */
    public function testFindUnlinkedWithLimit(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'orphan.mp3', 'sermon_id' => 0],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn(' LIMIT 10');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findUnlinked(10);

        $this->assertCount(1, $result);
    }

    /**
     * Test countUnlinked returns count of unlinked files.
     */
    public function testCountUnlinkedReturnsCount(): void
    {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('5');

        $result = $this->repository->countUnlinked();

        $this->assertSame(5, $result);
    }

    /**
     * Test countLinked returns count of linked files.
     */
    public function testCountLinkedReturnsCount(): void
    {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('25');

        $result = $this->repository->countLinked();

        $this->assertSame(25, $result);
    }

    /**
     * Test getTotalDownloads returns sum of all downloads.
     */
    public function testGetTotalDownloadsReturnsSum(): void
    {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1500');

        $result = $this->repository->getTotalDownloads();

        $this->assertSame(1500, $result);
    }

    /**
     * Test getTotalDownloads returns 0 when no downloads.
     */
    public function testGetTotalDownloadsReturnsZeroWhenNoDownloads(): void
    {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $result = $this->repository->getTotalDownloads();

        $this->assertSame(0, $result);
    }

    /**
     * Test countByType returns count of files by type.
     */
    public function testCountByTypeReturnsCount(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('type = %s', 'file')
            ->andReturn("type = 'file'");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('30');

        $result = $this->repository->countByType('file');

        $this->assertSame(30, $result);
    }

    /**
     * Test existsByName returns true when file exists.
     */
    public function testExistsByNameReturnsTrueWhenExists(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'sermon.mp3')
            ->andReturn("name = 'sermon.mp3'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([(object) ['id' => 1]]);

        $result = $this->repository->existsByName('sermon.mp3');

        $this->assertTrue($result);
    }

    /**
     * Test existsByName returns false when file does not exist.
     */
    public function testExistsByNameReturnsFalseWhenNotExists(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('name = %s', 'nonexistent.mp3')
            ->andReturn("name = 'nonexistent.mp3'");

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(' LIMIT %d OFFSET %d', 1, 0)
            ->andReturn(' LIMIT 1 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $result = $this->repository->existsByName('nonexistent.mp3');

        $this->assertFalse($result);
    }

    /**
     * Test unlinkFromSermon unlinks all files from sermon.
     */
    public function testUnlinkFromSermonUnlinksFiles(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE wp_sb_stuff SET sermon_id = 0 WHERE sermon_id = 42 AND type = 'file'");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(3);

        $result = $this->repository->unlinkFromSermon(42);

        $this->assertTrue($result);
    }

    /**
     * Test linkToSermon links file to sermon.
     */
    public function testLinkToSermonLinksFile(): void
    {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sb_stuff',
                ['sermon_id' => 42],
                ['id' => 1]
            )
            ->andReturn(1);

        $result = $this->repository->linkToSermon(1, 42);

        $this->assertTrue($result);
    }

    /**
     * Test deleteNonFilesBySermon deletes non-file attachments.
     */
    public function testDeleteNonFilesBySermonDeletesNonFiles(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("DELETE FROM wp_sb_stuff WHERE sermon_id = 42 AND type <> 'file'");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(2);

        $result = $this->repository->deleteNonFilesBySermon(42);

        $this->assertTrue($result);
    }

    /**
     * Test deleteByIds deletes files by IDs.
     */
    public function testDeleteByIdsDeletesFiles(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('DELETE FROM wp_sb_stuff WHERE id IN (1, 2, 3)');

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(3);

        $result = $this->repository->deleteByIds([1, 2, 3]);

        $this->assertTrue($result);
    }

    /**
     * Test deleteByIds returns true for empty array.
     */
    public function testDeleteByIdsReturnsTrueForEmptyArray(): void
    {
        $result = $this->repository->deleteByIds([]);

        $this->assertTrue($result);
    }

    /**
     * Test deleteOrphanedNonFiles deletes orphaned non-file attachments.
     */
    public function testDeleteOrphanedNonFilesDeletesOrphans(): void
    {
        $this->wpdb->shouldReceive('query')
            ->once()
            ->with("DELETE FROM wp_sb_stuff WHERE type != 'file' AND sermon_id = 0")
            ->andReturn(5);

        $result = $this->repository->deleteOrphanedNonFiles();

        $this->assertTrue($result);
    }

    /**
     * Test deleteEmptyUnlinked deletes empty unlinked files.
     */
    public function testDeleteEmptyUnlinkedDeletesEmptyFiles(): void
    {
        $this->wpdb->shouldReceive('query')
            ->once()
            ->with("DELETE FROM wp_sb_stuff WHERE type = 'file' AND name = '' AND sermon_id = 0")
            ->andReturn(2);

        $result = $this->repository->deleteEmptyUnlinked();

        $this->assertTrue($result);
    }

    /**
     * Test findAllFileNames returns array of file names.
     */
    public function testFindAllFileNamesReturnsNames(): void
    {
        $expectedNames = ['sermon1.mp3', 'sermon2.mp3', 'notes.pdf'];

        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn($expectedNames);

        $result = $this->repository->findAllFileNames();

        $this->assertCount(3, $result);
        $this->assertSame('sermon1.mp3', $result[0]);
    }

    /**
     * Test findBySermonOrUnlinked returns files for sermon or unlinked.
     */
    public function testFindBySermonOrUnlinkedReturnsFiles(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'sermon.mp3', 'sermon_id' => 42],
            (object) ['id' => 2, 'name' => 'orphan.mp3', 'sermon_id' => 0],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT * FROM wp_sb_stuff WHERE sermon_id IN (0, 42) AND type = 'file' ORDER BY name ASC");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findBySermonOrUnlinked(42);

        $this->assertCount(2, $result);
    }

    /**
     * Test deleteUnlinkedByName deletes unlinked file by name.
     */
    public function testDeleteUnlinkedByNameDeletesFile(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("DELETE FROM wp_sb_stuff WHERE name = 'orphan.mp3' AND sermon_id = 0");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(1);

        $result = $this->repository->deleteUnlinkedByName('orphan.mp3');

        $this->assertTrue($result);
    }

    /**
     * Test findUnlinkedWithTitle returns unlinked files with title.
     */
    public function testFindUnlinkedWithTitleReturnsFiles(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'orphan.mp3', 'sermon_id' => 0, 'title' => null],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findUnlinkedWithTitle();

        $this->assertCount(1, $result);
    }

    /**
     * Test findLinkedWithTitle returns linked files with title.
     */
    public function testFindLinkedWithTitleReturnsFiles(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'sermon.mp3', 'sermon_id' => 42, 'title' => 'Sunday Sermon'],
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->findLinkedWithTitle();

        $this->assertCount(1, $result);
        $this->assertSame('Sunday Sermon', $result[0]->title);
    }

    /**
     * Test searchByName returns matching files.
     */
    public function testSearchByNameReturnsMatchingFiles(): void
    {
        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'sermon-2024.mp3', 'title' => 'Sermon'],
        ];

        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('2024')
            ->andReturn('2024');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT f.*, s.title FROM wp_sb_stuff AS f...");

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($expectedFiles);

        $result = $this->repository->searchByName('2024');

        $this->assertCount(1, $result);
        $this->assertStringContainsString('2024', $result[0]->name);
    }

    /**
     * Test countBySearch returns count of matching files.
     */
    public function testCountBySearchReturnsCount(): void
    {
        $this->wpdb->shouldReceive('esc_like')
            ->once()
            ->with('sermon')
            ->andReturn('sermon');

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT COUNT(*) FROM wp_sb_stuff WHERE name LIKE '%sermon%' AND type = 'file'");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('15');

        $result = $this->repository->countBySearch('sermon');

        $this->assertSame(15, $result);
    }

    /**
     * Test getFileDuration returns duration for file.
     */
    public function testGetFileDurationReturnsDuration(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT duration FROM wp_sb_stuff WHERE type = 'file' AND name = 'sermon.mp3'");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('45:30');

        $result = $this->repository->getFileDuration('sermon.mp3');

        $this->assertSame('45:30', $result);
    }

    /**
     * Test getFileDuration returns null when not found.
     */
    public function testGetFileDurationReturnsNullWhenNotFound(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT duration FROM wp_sb_stuff...");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $result = $this->repository->getFileDuration('nonexistent.mp3');

        $this->assertNull($result);
    }

    /**
     * Test getFileDuration returns null for empty duration.
     */
    public function testGetFileDurationReturnsNullForEmptyDuration(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("SELECT duration FROM wp_sb_stuff...");

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('');

        $result = $this->repository->getFileDuration('sermon.mp3');

        $this->assertNull($result);
    }

    /**
     * Test setFileDuration sets duration for file.
     */
    public function testSetFileDurationSetsDuration(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE wp_sb_stuff SET duration = '45:30' WHERE type = 'file' AND name = 'sermon.mp3'");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(1);

        $result = $this->repository->setFileDuration('sermon.mp3', '45:30');

        $this->assertTrue($result);
    }

    /**
     * Test setFileDuration returns false on failure.
     */
    public function testSetFileDurationReturnsFalseOnFailure(): void
    {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn("UPDATE...");

        $this->wpdb->shouldReceive('query')
            ->once()
            ->andReturn(false);

        $result = $this->repository->setFileDuration('nonexistent.mp3', '45:30');

        $this->assertFalse($result);
    }
}
