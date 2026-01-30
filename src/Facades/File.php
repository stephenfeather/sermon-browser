<?php
/**
 * File Facade.
 *
 * Static access to FileRepository methods.
 *
 * @package SermonBrowser\Facades
 * @since 0.6.0
 *
 * @method static object|null find(int $id)
 * @method static array findAll(array $criteria = [], int $limit = 0, int $offset = 0, string $orderBy = 'id', string $order = 'ASC')
 * @method static int count(array $criteria = [])
 * @method static int create(array $data)
 * @method static bool update(int $id, array $data)
 * @method static bool delete(int $id)
 * @method static array findBy(string $column, mixed $value)
 * @method static object|null findOneBy(string $column, mixed $value)
 * @method static bool exists(int $id)
 * @method static array findBySermon(int $sermonId)
 * @method static array findBySermonAndType(int $sermonId, string $type)
 * @method static int countBySermon(int $sermonId)
 * @method static bool incrementCount(int $id)
 * @method static bool incrementCountByName(string $name)
 * @method static int getTotalDownloadsBySermon(int $sermonId)
 * @method static array findUnlinked(int $limit = 0)
 * @method static array findLinked(int $limit = 0)
 * @method static int countUnlinked()
 * @method static int countLinked()
 * @method static int getTotalDownloads()
 * @method static int countByType(string $type)
 * @method static bool existsByName(string $name)
 * @method static object|null findByName(string $name)
 * @method static bool unlinkFromSermon(int $sermonId)
 * @method static bool linkToSermon(int $fileId, int $sermonId)
 * @method static bool deleteNonFilesBySermon(int $sermonId)
 * @method static bool deleteByIds(array $ids)
 * @method static bool deleteOrphanedNonFiles()
 * @method static bool deleteEmptyUnlinked()
 * @method static array findAllFileNames()
 * @method static array findBySermonOrUnlinked(int $sermonId)
 * @method static bool deleteUnlinkedByName(string $name)
 * @method static array findUnlinkedWithTitle(int $limit = 0, int $offset = 0)
 * @method static array findLinkedWithTitle(int $limit = 0, int $offset = 0)
 * @method static array searchByName(string $search, int $limit = 0, int $offset = 0)
 * @method static int countBySearch(string $search)
 * @method static object|null getMostPopularSermon()
 */

declare(strict_types=1);

namespace SermonBrowser\Facades;

use SermonBrowser\Repositories\FileRepository;

/**
 * Class File
 *
 * Facade for FileRepository.
 */
class File extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getRepository(): FileRepository
    {
        return static::getContainer()->files();
    }
}
