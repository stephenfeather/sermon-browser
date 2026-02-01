<?php

/**
 * Tag Repository.
 *
 * Handles all database operations for sermon tags.
 * This manages both the sb_tags and sb_sermons_tags tables.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

use SermonBrowser\Constants;

/**
 * Class TagRepository
 *
 * Repository for the sb_tags and sb_sermons_tags tables.
 */
class TagRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = Constants::TABLE_TAGS;

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'name',
    ];

    /**
     * Get the sermon-tags pivot table name.
     *
     * @return string The table name.
     */
    protected function getPivotTableName(): string
    {
        return $this->db->prefix . Constants::TABLE_SERMONS_TAGS;
    }

    /**
     * Find tag by name.
     *
     * @param string $name The tag name.
     * @return object|null The tag or null.
     */
    public function findByName(string $name): ?object
    {
        return $this->findOneBy('name', $name);
    }

    /**
     * Get or create a tag by name.
     *
     * @param string $name The tag name.
     * @return int The tag ID.
     */
    public function findOrCreate(string $name): int
    {
        $existing = $this->findByName($name);

        if ($existing !== null) {
            return (int) $existing->id;
        }

        return $this->create(['name' => $name]);
    }

    /**
     * Get all tags sorted by name.
     *
     * @return array<object> Array of all tags.
     */
    public function findAllSorted(): array
    {
        return $this->findAll([], 0, 0, 'name', 'ASC');
    }

    /**
     * Search tags by name.
     *
     * @param string $search The search term.
     * @return array<object> Array of matching tags.
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
     * Get tags for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return array<object> Array of tags.
     */
    public function findBySermon(int $sermonId): array
    {
        $table = $this->getTableName();
        $pivotTable = $this->getPivotTableName();

        $sql = $this->db->prepare(
            "SELECT t.* FROM {$table} t
             INNER JOIN {$pivotTable} st ON t.id = st.tag_id
             WHERE st.sermon_id = %d
             ORDER BY t.name ASC",
            $sermonId
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get tag names for a sermon as a comma-separated string.
     *
     * @param int $sermonId The sermon ID.
     * @return string Comma-separated tag names.
     */
    public function getTagNamesForSermon(int $sermonId): string
    {
        $tags = $this->findBySermon($sermonId);
        $names = array_map(fn($tag) => $tag->name, $tags);

        return implode(', ', $names);
    }

    /**
     * Attach a tag to a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @param int $tagId The tag ID.
     * @return bool True on success.
     */
    public function attachToSermon(int $sermonId, int $tagId): bool
    {
        $pivotTable = $this->getPivotTableName();

        // Check if already attached
        $exists = $this->db->get_var(
            $this->db->prepare(
                "SELECT id FROM {$pivotTable} WHERE sermon_id = %d AND tag_id = %d",
                $sermonId,
                $tagId
            )
        );

        if ($exists) {
            return true; // Already attached
        }

        $result = $this->db->insert(
            $pivotTable,
            [
                'sermon_id' => $sermonId,
                'tag_id' => $tagId,
            ],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Detach a tag from a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @param int $tagId The tag ID.
     * @return bool True on success.
     */
    public function detachFromSermon(int $sermonId, int $tagId): bool
    {
        $pivotTable = $this->getPivotTableName();

        $result = $this->db->delete(
            $pivotTable,
            [
                'sermon_id' => $sermonId,
                'tag_id' => $tagId,
            ],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Sync tags for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @param array<int> $tagIds Array of tag IDs to sync.
     * @return bool True on success.
     */
    public function syncSermonTags(int $sermonId, array $tagIds): bool
    {
        // Remove all existing tags
        $this->detachAllFromSermon($sermonId);

        // Attach new tags
        foreach ($tagIds as $tagId) {
            $this->attachToSermon($sermonId, (int) $tagId);
        }

        return true;
    }

    /**
     * Detach all tags from a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return bool True on success.
     */
    public function detachAllFromSermon(int $sermonId): bool
    {
        $pivotTable = $this->getPivotTableName();

        $result = $this->db->delete(
            $pivotTable,
            ['sermon_id' => $sermonId],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get tags with sermon counts (for tag cloud).
     *
     * @param int $limit Maximum tags to return.
     * @return array<object> Array of tags with counts.
     */
    public function findAllWithSermonCount(int $limit = 0): array
    {
        $table = $this->getTableName();
        $pivotTable = $this->getPivotTableName();

        $sql = "SELECT t.*, COUNT(st.id) as sermon_count
                FROM {$table} t
                LEFT JOIN {$pivotTable} st ON t.id = st.tag_id
                GROUP BY t.id
                HAVING sermon_count > 0
                ORDER BY sermon_count DESC";

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d', $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Find sermons by tag ID.
     *
     * @param int $tagId The tag ID.
     * @return array<int> Array of sermon IDs.
     */
    public function getSermonIdsByTag(int $tagId): array
    {
        $pivotTable = $this->getPivotTableName();

        $results = $this->db->get_col(
            $this->db->prepare(
                "SELECT sermon_id FROM {$pivotTable} WHERE tag_id = %d",
                $tagId
            )
        );

        return array_map('intval', $results);
    }

    /**
     * Delete unused tags (tags with no sermons).
     *
     * @return int Number of deleted tags.
     */
    public function deleteUnused(): int
    {
        $table = $this->getTableName();
        $pivotTable = $this->getPivotTableName();

        $sql = "DELETE t FROM {$table} t
                LEFT JOIN {$pivotTable} st ON t.id = st.tag_id
                WHERE st.id IS NULL";

        $result = $this->db->query($sql);

        return $result !== false ? (int) $this->db->rows_affected : 0;
    }

    /**
     * Count tags with non-empty names.
     *
     * @return int Count of non-empty tags.
     */
    public function countNonEmpty(): int
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE name <> ''"
        );

        return (int) ($result ?? 0);
    }
}
