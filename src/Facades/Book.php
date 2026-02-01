<?php

/**
 * Book Facade.
 *
 * Static access to BookRepository methods for Bible book and passage operations.
 *
 * @package SermonBrowser\Facades
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Facades;

use SermonBrowser\Repositories\BookRepository;

/**
 * Class Book
 *
 * Facade for BookRepository.
 *
 * @method static bool truncate()
 * @method static int insertBook(string $name)
 * @method static bool updateBookNameInSermons(string $newName, string $oldName)
 * @method static bool deleteBySermonId(int $sermonId)
 * @method static int insertPassageRef(string $book, string $chapter, string $verse, int $order, string $type, int $sermonId)
 * @method static array findBySermonId(int $sermonId)
 * @method static void resetBooksForLocale(array $books, array $engBooks)
 * @method static array getSermonsWithVerseData()
 * @method static bool updateSermonVerseData(int $sermonId, string $start, string $end)
 * @method static array findAllNames()
 * @method static array findAllWithSermonCount()
 * @method static array findBySermonIdsWithCount(array $sermonIds)
 */
class Book extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getRepository(): BookRepository
    {
        return static::getContainer()->books();
    }
}
