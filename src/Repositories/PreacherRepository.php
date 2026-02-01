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

use SermonBrowser\Constants;

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
    protected string $tableSuffix = Constants::TABLE_PREACHERS;

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

    /**
     * Get all preachers with sermon counts for filter dropdowns.
     *
     * Returns preachers ordered by sermon count DESC, then by most recent sermon.
     * Result objects have: id, name, description, image, count.
     *
     * @return array<object> Array of preachers with count.
     */
    public function findAllForFilter(): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT p.*, COUNT(p.id) AS count
                FROM {$table} p
                JOIN {$sermonsTable} s ON p.id = s.preacher_id
                GROUP BY p.id
                ORDER BY count DESC, s.datetime DESC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get preachers with counts for specific sermon IDs (oneclick filter).
     *
     * Returns preachers who preached the given sermons, with counts.
     * Result objects have: id, name, description, image, count.
     *
     * @param array<int> $sermonIds Array of sermon IDs to filter by.
     * @return array<object> Array of preachers with count.
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
            "SELECT p.*, COUNT(p.id) AS count
             FROM {$table} p
             JOIN {$sermonsTable} sermons ON p.id = sermons.preacher_id
             WHERE sermons.id IN ({$placeholders})
             GROUP BY p.id
             ORDER BY count DESC, sermons.datetime DESC",
            ...$sermonIds
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Find preacher by name using case-insensitive LIKE match.
     *
     * Used for ID3 import to match existing preachers.
     *
     * @param string $name The preacher name to search for.
     * @return object|null The preacher or null.
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
     * Find or create a preacher by name.
     *
     * Used for ID3 import to auto-create preachers from artist tags.
     * Uses case-insensitive matching to find existing preachers.
     *
     * @param string $name The preacher name.
     * @return int The preacher ID (existing or newly created).
     */
    public function findOrCreate(string $name): int
    {
        if ($name === '') {
            return 0;
        }

        // Try to find existing preacher (case-insensitive)
        $existing = $this->findByNameLike($name);
        if ($existing !== null) {
            return (int) $existing->id;
        }

        // Create new preacher with empty description and image
        return $this->create([
            'name' => $name,
            'description' => '',
            'image' => '',
        ]);
    }
}
