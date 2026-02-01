<?php

/**
 * Tag Facade.
 *
 * Static access to TagRepository methods.
 *
 * @package SermonBrowser\Facades
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Facades;

use SermonBrowser\Repositories\TagRepository;

/**
 * Class Tag
 *
 * Facade for TagRepository.
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
 * @method static object|null findByName(string $name)
 * @method static int findOrCreate(string $name)
 * @method static array findAllSorted()
 * @method static array findBySermon(int $sermonId)
 * @method static bool attachToSermon(int $sermonId, int $tagId)
 * @method static bool detachFromSermon(int $sermonId, int $tagId)
 * @method static bool detachAllFromSermon(int $sermonId)
 * @method static array findAllWithSermonCount(int $limit = 0)
 * @method static int deleteUnused()
 * @method static int countNonEmpty()
 */
class Tag extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getRepository(): TagRepository
    {
        return static::getContainer()->tags();
    }
}
