<?php

/**
 * Sermon Facade.
 *
 * Static access to SermonRepository methods.
 *
 * @package SermonBrowser\Facades
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Facades;

use SermonBrowser\Repositories\SermonRepository;

/**
 * Class Sermon
 *
 * Facade for SermonRepository.
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
 * @method static array findByPreacher(int $preacherId, int $limit = 0)
 * @method static array findBySeries(int $seriesId, int $limit = 0)
 * @method static array findByService(int $serviceId, int $limit = 0)
 * @method static array findRecent(int $limit = 10)
 * @method static array findByDateRange(string $startDate, string $endDate, int $limit = 0)
 * @method static object|null findWithRelations(int $id)
 * @method static array findAllWithRelations(array $filter = [], int $limit = 0, int $offset = 0)
 * @method static int countFiltered(array $filter = [])
 * @method static array searchByTitle(string $search, int $limit = 0)
 * @method static array findForAdminListFiltered(array $filter = [], int $limit = 0, int $offset = 0)
 * @method static object|null findNextByDate(string $datetime, int $excludeId)
 * @method static object|null findPreviousByDate(string $datetime, int $excludeId)
 * @method static array findSameDay(string $datetime, int $excludeId)
 * @method static object|null findForTemplate(int $id)
 * @method static array findDatesForIds(array $sermonIds)
 * @method static array findForFrontendListing(array $filter = [], array $order = [], int $page = 1, int $limit = 0, bool $hideEmpty = false)
 */
class Sermon extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getRepository(): SermonRepository
    {
        return static::getContainer()->sermons();
    }
}
