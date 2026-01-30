<?php
/**
 * File Repository.
 *
 * Handles all database operations for sermon files/attachments.
 * This maps to the sb_stuff table which stores files associated with sermons.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

/**
 * Class FileRepository
 *
 * Repository for the sb_stuff table (sermon attachments/files).
 */
class FileRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = 'sb_stuff';

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'type',
        'name',
        'sermon_id',
        'count',
        'duration',
    ];

    /**
     * Find all files for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return array<object> Array of files.
     */
    public function findBySermon(int $sermonId): array
    {
        return $this->findAll(['sermon_id' => $sermonId]);
    }

    /**
     * Find files by type for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @param string $type The file type (e.g., 'mp3', 'pdf').
     * @return array<object> Array of files.
     */
    public function findBySermonAndType(int $sermonId, string $type): array
    {
        return $this->findAll([
            'sermon_id' => $sermonId,
            'type' => $type,
        ]);
    }

    /**
     * Find all files of a specific type.
     *
     * @param string $type The file type.
     * @return array<object> Array of files.
     */
    public function findByType(string $type): array
    {
        return $this->findAll(['type' => $type]);
    }

    /**
     * Count files for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return int The file count.
     */
    public function countBySermon(int $sermonId): int
    {
        return $this->count(['sermon_id' => $sermonId]);
    }

    /**
     * Delete all files for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return bool True on success.
     */
    public function deleteBySermon(int $sermonId): bool
    {
        $table = $this->getTableName();

        $result = $this->db->delete(
            $table,
            ['sermon_id' => $sermonId],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get distinct file types used.
     *
     * @return array<string> Array of file types.
     */
    public function getTypes(): array
    {
        $table = $this->getTableName();

        $results = $this->db->get_col(
            "SELECT DISTINCT type FROM {$table} ORDER BY type ASC"
        );

        return is_array($results) ? $results : [];
    }

    /**
     * Increment download count for a file.
     *
     * @param int $id The file ID.
     * @return bool True on success.
     */
    public function incrementCount(int $id): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$table} SET count = count + 1 WHERE id = %d",
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Increment download count for a file by name.
     *
     * @param string $name The file name.
     * @return bool True on success.
     */
    public function incrementCountByName(string $name): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$table} SET count = count + 1 WHERE name = %s",
                $name
            )
        );

        return $result !== false;
    }

    /**
     * Get files with sermon data.
     *
     * @param string $type Optional file type filter.
     * @param int $limit Maximum results.
     * @return array<object> Array of files with sermon data.
     */
    public function findAllWithSermon(string $type = '', int $limit = 0): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT f.*, s.title as sermon_title, s.datetime as sermon_datetime
                FROM {$table} f
                LEFT JOIN {$sermonsTable} s ON f.sermon_id = s.id
                WHERE 1=1";

        if (!empty($type)) {
            $sql .= $this->db->prepare(' AND f.type = %s', $type);
        }

        $sql .= ' ORDER BY s.datetime DESC';

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d', $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get total download counts by type.
     *
     * @return array<object> Array with type and total counts.
     */
    public function getDownloadStatsByType(): array
    {
        $table = $this->getTableName();

        $sql = "SELECT type, SUM(count) as total_downloads, COUNT(*) as file_count
                FROM {$table}
                GROUP BY type
                ORDER BY total_downloads DESC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get total download count for a sermon.
     *
     * @param int $sermonId The sermon ID.
     * @return int Total download count.
     */
    public function getTotalDownloadsBySermon(int $sermonId): int
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            $this->db->prepare(
                "SELECT SUM(count) FROM {$table} WHERE sermon_id = %d",
                $sermonId
            )
        );

        return (int) ($result ?? 0);
    }
}
