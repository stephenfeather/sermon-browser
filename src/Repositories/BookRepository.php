<?php
/**
 * Book Repository.
 *
 * Handles database operations for Bible books and sermon-book relationships.
 * Manages two tables: sb_books (book names) and sb_books_sermons (passage references).
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

/**
 * Class BookRepository
 *
 * Repository for Bible book operations.
 */
class BookRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = 'sb_books';

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'name',
    ];

    /**
     * Get the books_sermons table name.
     *
     * @return string The table name with prefix.
     */
    public function getBooksSermonTableName(): string
    {
        return $this->db->prefix . 'sb_books_sermons';
    }

    /**
     * Truncate the books table.
     *
     * @return bool True on success.
     */
    public function truncate(): bool
    {
        $table = $this->getTableName();
        $result = $this->db->query("TRUNCATE TABLE {$table}");

        return $result !== false;
    }

    /**
     * Insert a book name.
     *
     * @param string $name The book name.
     * @return int The insert ID.
     */
    public function insertBook(string $name): int
    {
        $table = $this->getTableName();
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO {$table} VALUES (null, %s)",
                $name
            )
        );

        return (int) $this->db->insert_id;
    }

    /**
     * Update book name in books_sermons table.
     *
     * @param string $newName The new book name.
     * @param string $oldName The old book name.
     * @return bool True on success.
     */
    public function updateBookNameInSermons(string $newName, string $oldName): bool
    {
        $table = $this->getBooksSermonTableName();
        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$table} SET book_name = %s WHERE book_name = %s",
                $newName,
                $oldName
            )
        );

        return $result !== false;
    }

    /**
     * Delete all passage references for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return bool True on success.
     */
    public function deleteBySermonId(int $sermonId): bool
    {
        $table = $this->getBooksSermonTableName();
        $result = $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$table} WHERE sermon_id = %d",
                $sermonId
            )
        );

        return $result !== false;
    }

    /**
     * Insert a passage reference for a sermon.
     *
     * @param string $book The book name.
     * @param string $chapter The chapter number.
     * @param string $verse The verse number.
     * @param int $order The order index.
     * @param string $type The type ('start' or 'end').
     * @param int $sermonId The sermon ID.
     * @return int The insert ID.
     */
    public function insertPassageRef(
        string $book,
        string $chapter,
        string $verse,
        int $order,
        string $type,
        int $sermonId
    ): int {
        $table = $this->getBooksSermonTableName();
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO {$table} VALUES (null, %s, %s, %s, %d, %s, %d)",
                $book,
                $chapter,
                $verse,
                $order,
                $type,
                $sermonId
            )
        );

        return (int) $this->db->insert_id;
    }

    /**
     * Get all passage references for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return array<object> Array of passage references.
     */
    public function findBySermonId(int $sermonId): array
    {
        $table = $this->getBooksSermonTableName();
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$table} WHERE sermon_id = %d ORDER BY `order` ASC",
                $sermonId
            )
        );

        return is_array($results) ? $results : [];
    }

    /**
     * Reset and repopulate Bible books from locale.
     *
     * Truncates the books table and inserts all book names,
     * also updates book names in books_sermons table.
     *
     * @param array<string> $books The localized book names.
     * @param array<string> $engBooks The English book names (for mapping).
     * @return void
     */
    public function resetBooksForLocale(array $books, array $engBooks): void
    {
        $this->truncate();

        for ($i = 0; $i < count($books); $i++) {
            $this->insertBook($books[$i]);
            $this->updateBookNameInSermons($books[$i], $engBooks[$i]);
        }
    }

    /**
     * Get all sermons with start/end verse data for locale migration.
     *
     * @return array<object> Array of sermons with id, start, end columns.
     */
    public function getSermonsWithVerseData(): array
    {
        $table = $this->db->prefix . 'sb_sermons';
        $results = $this->db->get_results(
            "SELECT id, start, end FROM {$table}"
        );

        return is_array($results) ? $results : [];
    }

    /**
     * Update sermon verse data (start/end columns).
     *
     * @param int $sermonId The sermon ID.
     * @param string $start Serialized start verse data.
     * @param string $end Serialized end verse data.
     * @return bool True on success.
     */
    public function updateSermonVerseData(int $sermonId, string $start, string $end): bool
    {
        $table = $this->db->prefix . 'sb_sermons';
        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$table} SET start = %s, end = %s WHERE id = %d",
                $start,
                $end,
                $sermonId
            )
        );

        return $result !== false;
    }

    /**
     * Get all book names ordered by ID.
     *
     * Returns a simple array of book names for dropdown lists.
     *
     * @return array<string> Array of book names.
     */
    public function findAllNames(): array
    {
        $table = $this->getTableName();
        $results = $this->db->get_col(
            "SELECT name FROM {$table} ORDER BY id"
        );

        return is_array($results) ? $results : [];
    }

    /**
     * Get all books with sermon counts for filter dropdowns.
     *
     * Returns books that have at least one sermon reference, with counts.
     * Result objects have: name, count (distinct sermon count).
     *
     * @return array<object> Array of books with count.
     */
    public function findAllWithSermonCount(): array
    {
        $booksSermonTable = $this->getBooksSermonTableName();
        $booksTable = $this->getTableName();

        $sql = "SELECT bs.book_name AS name, COUNT(DISTINCT bs.sermon_id) AS count
                FROM {$booksSermonTable} bs
                JOIN {$booksTable} b ON bs.book_name = b.name
                GROUP BY b.id";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get books with counts for specific sermon IDs (oneclick filter).
     *
     * Returns books referenced by the given sermons, with counts.
     * Result objects have: name, count (distinct sermon count).
     *
     * @param array<int> $sermonIds Array of sermon IDs to filter by.
     * @return array<object> Array of books with count.
     */
    public function findBySermonIdsWithCount(array $sermonIds): array
    {
        if (empty($sermonIds)) {
            return [];
        }

        $booksSermonTable = $this->getBooksSermonTableName();
        $booksTable = $this->getTableName();

        $placeholders = implode(', ', array_fill(0, count($sermonIds), '%d'));
        $sql = $this->db->prepare(
            "SELECT bs.book_name AS name, COUNT(DISTINCT bs.sermon_id) AS count
             FROM {$booksSermonTable} bs
             JOIN {$booksTable} b ON bs.book_name = b.name
             WHERE bs.sermon_id IN ({$placeholders})
             GROUP BY b.id",
            ...$sermonIds
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }
}
