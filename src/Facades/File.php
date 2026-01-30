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
