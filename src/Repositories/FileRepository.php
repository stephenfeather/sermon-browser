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

    /**
     * Find unlinked files (not associated with any sermon).
     *
     * @param int $limit Maximum results (0 for all).
     * @return array<object> Array of unlinked files.
     */
    public function findUnlinked(int $limit = 0): array
    {
        $table = $this->getTableName();

        $sql = "SELECT * FROM {$table} WHERE sermon_id = 0 AND type = 'file' ORDER BY name ASC";

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d', $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Find linked files (associated with a sermon).
     *
     * @param int $limit Maximum results (0 for all).
     * @return array<object> Array of linked files with sermon info.
     */
    public function findLinked(int $limit = 0): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT f.*, s.title FROM {$table} AS f
                LEFT JOIN {$sermonsTable} AS s ON f.sermon_id = s.id
                WHERE f.sermon_id <> 0 AND f.type = 'file'
                ORDER BY f.name ASC";

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d', $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Count unlinked files.
     *
     * @return int Count of unlinked files.
     */
    public function countUnlinked(): int
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE sermon_id = 0 AND type = 'file'"
        );

        return (int) ($result ?? 0);
    }

    /**
     * Count linked files.
     *
     * @return int Count of linked files.
     */
    public function countLinked(): int
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE sermon_id <> 0 AND type = 'file'"
        );

        return (int) ($result ?? 0);
    }

    /**
     * Get total download count across all files.
     *
     * @return int Total download count.
     */
    public function getTotalDownloads(): int
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            "SELECT SUM(count) FROM {$table}"
        );

        return (int) ($result ?? 0);
    }

    /**
     * Count files of a specific type.
     *
     * @param string $type The file type.
     * @return int Count of files.
     */
    public function countByType(string $type): int
    {
        return $this->count(['type' => $type]);
    }

    /**
     * Check if a file with the given name exists.
     *
     * @param string $name The file name.
     * @return bool True if exists.
     */
    public function existsByName(string $name): bool
    {
        $existing = $this->findOneBy('name', $name);
        return $existing !== null;
    }

    /**
     * Find file by name.
     *
     * @param string $name The file name.
     * @return object|null The file or null.
     */
    public function findByName(string $name): ?object
    {
        return $this->findOneBy('name', $name);
    }

    /**
     * Unlink all files from a sermon (set sermon_id = 0).
     *
     * @param int $sermonId The sermon ID.
     * @return bool True on success.
     */
    public function unlinkFromSermon(int $sermonId): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$table} SET sermon_id = 0 WHERE sermon_id = %d AND type = 'file'",
                $sermonId
            )
        );

        return $result !== false;
    }

    /**
     * Link a file to a sermon.
     *
     * @param int $fileId The file ID.
     * @param int $sermonId The sermon ID.
     * @return bool True on success.
     */
    public function linkToSermon(int $fileId, int $sermonId): bool
    {
        return $this->update($fileId, ['sermon_id' => $sermonId]);
    }

    /**
     * Delete non-file attachments for a sermon (urls, codes).
     *
     * @param int $sermonId The sermon ID.
     * @return bool True on success.
     */
    public function deleteNonFilesBySermon(int $sermonId): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$table} WHERE sermon_id = %d AND type <> 'file'",
                $sermonId
            )
        );

        return $result !== false;
    }

    /**
     * Delete files by IDs.
     *
     * @param array<int> $ids Array of file IDs.
     * @return bool True on success.
     */
    public function deleteByIds(array $ids): bool
    {
        if (empty($ids)) {
            return true;
        }

        $table = $this->getTableName();
        $placeholders = implode(', ', array_fill(0, count($ids), '%d'));

        $result = $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$table} WHERE id IN ({$placeholders})",
                ...$ids
            )
        );

        return $result !== false;
    }

    /**
     * Delete orphaned non-file attachments (sermon_id = 0 and type != 'file').
     *
     * @return bool True on success.
     */
    public function deleteOrphanedNonFiles(): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            "DELETE FROM {$table} WHERE type != 'file' AND sermon_id = 0"
        );

        return $result !== false;
    }

    /**
     * Delete empty unlinked files.
     *
     * @return bool True on success.
     */
    public function deleteEmptyUnlinked(): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            "DELETE FROM {$table} WHERE type = 'file' AND name = '' AND sermon_id = 0"
        );

        return $result !== false;
    }

    /**
     * Get all file names.
     *
     * @return array<string> Array of file names.
     */
    public function findAllFileNames(): array
    {
        $table = $this->getTableName();

        $results = $this->db->get_col(
            "SELECT name FROM {$table} WHERE type = 'file'"
        );

        return is_array($results) ? $results : [];
    }

    /**
     * Find files for a sermon or unlinked files.
     *
     * @param int $sermonId The sermon ID.
     * @return array<object> Array of files.
     */
    public function findBySermonOrUnlinked(int $sermonId): array
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE sermon_id IN (0, %d) AND type = 'file' ORDER BY name ASC",
            $sermonId
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Delete unlinked file by name.
     *
     * @param string $name The file name.
     * @return bool True on success.
     */
    public function deleteUnlinkedByName(string $name): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$table} WHERE name = %s AND sermon_id = 0",
                $name
            )
        );

        return $result !== false;
    }

    /**
     * Find unlinked files with sermon title (for file management page).
     *
     * @param int $limit Maximum results (0 for all).
     * @param int $offset Number to skip.
     * @return array<object> Array of files with title.
     */
    public function findUnlinkedWithTitle(int $limit = 0, int $offset = 0): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT f.*, s.title FROM {$table} AS f
                LEFT JOIN {$sermonsTable} AS s ON f.sermon_id = s.id
                WHERE f.sermon_id = 0 AND f.type = 'file'
                ORDER BY f.name ASC";

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d OFFSET %d', $limit, $offset);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Find linked files with sermon title (for file management page).
     *
     * @param int $limit Maximum results (0 for all).
     * @param int $offset Number to skip.
     * @return array<object> Array of files with title.
     */
    public function findLinkedWithTitle(int $limit = 0, int $offset = 0): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT f.*, s.title FROM {$table} AS f
                LEFT JOIN {$sermonsTable} AS s ON f.sermon_id = s.id
                WHERE f.sermon_id <> 0 AND f.type = 'file'
                ORDER BY f.name ASC";

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d OFFSET %d', $limit, $offset);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Search files by name with sermon title.
     *
     * @param string $search The search term.
     * @param int $limit Maximum results (0 for all).
     * @param int $offset Number to skip.
     * @return array<object> Array of matching files.
     */
    public function searchByName(string $search, int $limit = 0, int $offset = 0): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = $this->db->prepare(
            "SELECT f.*, s.title FROM {$table} AS f
            LEFT JOIN {$sermonsTable} AS s ON f.sermon_id = s.id
            WHERE f.name LIKE %s AND f.type = 'file'
            ORDER BY f.name ASC",
            '%' . $this->db->esc_like($search) . '%'
        );

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d OFFSET %d', $limit, $offset);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Count files matching search term.
     *
     * @param string $search The search term.
     * @return int The count.
     */
    public function countBySearch(string $search): int
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE name LIKE %s AND type = 'file'",
                '%' . $this->db->esc_like($search) . '%'
            )
        );

        return (int) ($result ?? 0);
    }

    /**
     * Get the most popular sermon by download count.
     *
     * @return object|null The most popular sermon data or null.
     */
    public function getMostPopularSermon(): ?object
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT s.title, f.sermon_id, SUM(f.count) AS c
                FROM {$table} f
                LEFT JOIN {$sermonsTable} s ON s.id = f.sermon_id
                GROUP BY f.sermon_id
                ORDER BY c DESC
                LIMIT 1";

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }
}
