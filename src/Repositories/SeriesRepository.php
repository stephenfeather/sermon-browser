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
    protected string $tableSuffix = 'sb_series';

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
}
