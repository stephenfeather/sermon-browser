<?php

/**
 * Series Repository.
 *
 * Handles all database operations for sermon series.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

use SermonBrowser\Constants;

/**
 * Class SeriesRepository
 *
 * Repository for the sb_series table.
 */
class SeriesRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = Constants::TABLE_SERIES;

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'name',
        'page_id',
    ];

    /**
     * Find series by name.
     *
     * @param string $name The series name.
     * @return object|null The series or null.
     */
    public function findByName(string $name): ?object
    {
        return $this->findOneBy('name', $name);
    }

    /**
     * Search series by name.
     *
     * @param string $search The search term.
     * @return array<object> Array of matching series.
     */
    public function searchByName(string $search): array
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE name LIKE %s ORDER BY name ASC",
            '%' . $this->db->esc_like($search) . '%'
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get all series sorted by name.
     *
     * @return array<object> Array of all series.
     */
    public function findAllSorted(): array
    {
        return $this->findAll([], 0, 0, 'name', 'ASC');
    }

    /**
     * Get series with sermon counts.
     *
     * @return array<object> Array of series with count.
     */
    public function findAllWithSermonCount(): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT s.*, COUNT(ser.id) as sermon_count
                FROM {$table} s
                LEFT JOIN {$sermonsTable} ser ON s.id = ser.series_id
                GROUP BY s.id
                ORDER BY s.name ASC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Check if series has sermons.
     *
     * @param int $id The series ID.
     * @return bool True if series has sermons.
     */
    public function hasSermons(int $id): bool
    {
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $count = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$sermonsTable} WHERE series_id = %d",
                $id
            )
        );

        return (int) $count > 0;
    }

    /**
     * Find series that have a linked page.
     *
     * @return array<object> Array of series with pages.
     */
    public function findWithPages(): array
    {
        $table = $this->getTableName();

        $sql = "SELECT * FROM {$table} WHERE page_id > 0 ORDER BY name ASC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get all series with sermon counts for filter dropdowns.
     *
     * Returns series ordered by most recent sermon datetime.
     * Result objects have: id, name, page_id, count.
     *
     * @return array<object> Array of series with count.
     */
    public function findAllForFilter(): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT ss.*, COUNT(ss.id) AS count
                FROM {$table} ss
                JOIN {$sermonsTable} sermons ON ss.id = sermons.series_id
                GROUP BY ss.id
                ORDER BY sermons.datetime DESC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get series with counts for specific sermon IDs (oneclick filter).
     *
     * Returns series containing the given sermons, with counts.
     * Result objects have: id, name, page_id, count.
     *
     * @param array<int> $sermonIds Array of sermon IDs to filter by.
     * @return array<object> Array of series with count.
     */
    public function findBySermonIdsWithCount(array $sermonIds): array
    {
        if (empty($sermonIds)) {
            return [];
        }

        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $placeholders = implode(', ', array_fill(0, count($sermonIds), '%d'));
        $sql = $this->db->prepare(
            "SELECT ss.*, COUNT(ss.id) AS count
             FROM {$table} ss
             JOIN {$sermonsTable} sermons ON ss.id = sermons.series_id
             WHERE sermons.id IN ({$placeholders})
             GROUP BY ss.id
             ORDER BY sermons.datetime DESC",
            ...$sermonIds
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Find series by name using case-insensitive LIKE match.
     *
     * Used for ID3 import to match existing series.
     *
     * @param string $name The series name to search for.
     * @return object|null The series or null.
     */
    public function findByNameLike(string $name): ?object
    {
        if ($name === '') {
            return null;
        }

        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE name LIKE %s LIMIT 1",
            $name
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * Find or create a series by name.
     *
     * Used for ID3 import to auto-create series from album tags.
     * Uses case-insensitive matching to find existing series.
     *
     * @param string $name The series name.
     * @return int The series ID (existing or newly created).
     */
    public function findOrCreate(string $name): int
    {
        if ($name === '') {
            return 0;
        }

        // Try to find existing series (case-insensitive)
        $existing = $this->findByNameLike($name);
        if ($existing !== null) {
            return (int) $existing->id;
        }

        // Create new series with default page_id of 0
        return $this->create([
            'name' => $name,
            'page_id' => 0,
        ]);
    }
}
