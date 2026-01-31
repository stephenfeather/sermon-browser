<?php
/**
 * Series Facade.
 *
 * Static access to SeriesRepository methods.
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
 * @method static array findAllForFilter()
 * @method static array findBySermonIdsWithCount(array $sermonIds)
 */

declare(strict_types=1);

namespace SermonBrowser\Facades;

use SermonBrowser\Repositories\SeriesRepository;

/**
 * Class Series
 *
 * Facade for SeriesRepository.
 */
class Series extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getRepository(): SeriesRepository
    {
        return static::getContainer()->series();
    }
}
