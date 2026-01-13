<?php
/**
 * Preacher Repository.
 *
 * Handles all database operations for preachers.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

/**
 * Class PreacherRepository
 *
 * Repository for the sb_preachers table.
 */
class PreacherRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = 'sb_preachers';

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'name',
        'description',
        'image',
    ];

    /**
     * Find preacher by name.
     *
     * @param string $name The preacher name.
     * @return object|null The preacher or null.
     */
    public function findByName(string $name): ?object
    {
        return $this->findOneBy('name', $name);
    }

    /**
     * Search preachers by name.
     *
     * @param string $search The search term.
     * @return array<object> Array of matching preachers.
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
     * Get all preachers sorted by name.
     *
     * @return array<object> Array of all preachers.
     */
    public function findAllSorted(): array
    {
        return $this->findAll([], 0, 0, 'name', 'ASC');
    }

    /**
     * Get preachers with sermon counts.
     *
     * @return array<object> Array of preachers with count.
     */
    public function findAllWithSermonCount(): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT p.*, COUNT(s.id) as sermon_count
                FROM {$table} p
                LEFT JOIN {$sermonsTable} s ON p.id = s.preacher_id
                GROUP BY p.id
                ORDER BY p.name ASC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Check if preacher has sermons.
     *
     * @param int $id The preacher ID.
     * @return bool True if preacher has sermons.
     */
    public function hasSermons(int $id): bool
    {
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $count = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$sermonsTable} WHERE preacher_id = %d",
                $id
            )
        );

        return (int) $count > 0;
    }
}
