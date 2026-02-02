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

use SermonBrowser\Constants;

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
    protected string $tableSuffix = Constants::TABLE_STUFF;

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
            $sql .= $this->db->prepare(Constants::SQL_LIMIT, $limit);
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
            $sql .= $this->db->prepare(Constants::SQL_LIMIT_OFFSET, $limit, $offset);
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
            $sql .= $this->db->prepare(Constants::SQL_LIMIT_OFFSET, $limit, $offset);
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
            $sql .= $this->db->prepare(Constants::SQL_LIMIT_OFFSET, $limit, $offset);
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

    /**
     * Get popular sermons by download count.
     *
     * @param int $limit Maximum number of sermons to return.
     * @return array<object> Array of sermon objects with id, title, and total downloads.
     */
    public function getPopularSermons(int $limit): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = $this->db->prepare(
            "SELECT sermons.id, sermons.title, SUM(stuff.count) AS total
             FROM {$table} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             GROUP BY sermons.id
             ORDER BY total DESC
             LIMIT %d",
            $limit
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get popular series using combined ranking algorithm.
     *
     * Ranks series by both average downloads per file and total downloads,
     * then combines the rankings (lower combined rank = more popular).
     *
     * @param int $limit Maximum number of series to return.
     * @return array<object> Array of series objects with id, name, and rank.
     */
    public function getPopularSeries(int $limit): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';
        $seriesTable = $this->db->prefix . 'sb_series';

        // Get series ranked by average downloads
        $byAverage = $this->db->get_results(
            "SELECT series.id, series.name, AVG(stuff.count) AS average
             FROM {$table} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$seriesTable} AS series ON sermons.series_id = series.id
             GROUP BY series.id
             ORDER BY average DESC"
        );

        // Get series ranked by total downloads
        $byTotal = $this->db->get_results(
            "SELECT series.id, SUM(stuff.count) AS total
             FROM {$table} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$seriesTable} AS series ON sermons.series_id = series.id
             GROUP BY series.id
             ORDER BY total DESC"
        );

        return $this->combineRankings($byAverage, $byTotal, $limit);
    }

    /**
     * Get popular preachers using combined ranking algorithm.
     *
     * Ranks preachers by both average downloads per file and total downloads,
     * then combines the rankings (lower combined rank = more popular).
     *
     * @param int $limit Maximum number of preachers to return.
     * @return array<object> Array of preacher objects with id, name, and rank.
     */
    public function getPopularPreachers(int $limit): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';
        $preachersTable = $this->db->prefix . 'sb_preachers';

        // Get preachers ranked by average downloads
        $byAverage = $this->db->get_results(
            "SELECT preachers.id, preachers.name, AVG(stuff.count) AS average
             FROM {$table} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$preachersTable} AS preachers ON sermons.preacher_id = preachers.id
             GROUP BY preachers.id
             ORDER BY average DESC"
        );

        // Get preachers ranked by total downloads
        $byTotal = $this->db->get_results(
            "SELECT preachers.id, SUM(stuff.count) AS total
             FROM {$table} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$preachersTable} AS preachers ON sermons.preacher_id = preachers.id
             GROUP BY preachers.id
             ORDER BY total DESC"
        );

        return $this->combineRankings($byAverage, $byTotal, $limit);
    }

    /**
     * Get duration for a file by name.
     *
     * @param string $name The file name.
     * @return string|null The duration or null if not found/empty.
     */
    public function getFileDuration(string $name): ?string
    {
        $table = $this->getTableName();

        $result = $this->db->get_var(
            $this->db->prepare(
                "SELECT duration FROM {$table} WHERE type = 'file' AND name = %s",
                $name
            )
        );

        return $result ?: null;
    }

    /**
     * Set duration for a file by name.
     *
     * @param string $name The file name.
     * @param string $duration The duration string.
     * @return bool True on success.
     */
    public function setFileDuration(string $name, string $duration): bool
    {
        $table = $this->getTableName();

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$table} SET duration = %s WHERE type = 'file' AND name = %s",
                $duration,
                $name
            )
        );

        return $result !== false;
    }

    /**
     * Combine two rankings into a single ranking.
     *
     * @param array<object> $byAverage Items ranked by average (with id, name).
     * @param array<object> $byTotal Items ranked by total (with id).
     * @param int $limit Maximum number of items to return.
     * @return array<object> Combined ranking.
     */
    private function combineRankings(array $byAverage, array $byTotal, int $limit): array
    {
        $combined = [];

        // Assign average ranking (1-based)
        $rank = 1;
        foreach ($byAverage as $item) {
            if ($item->id === null) {
                continue;
            }
            $combined[$item->id] = (object) [
                'id' => $item->id,
                'name' => $item->name,
                'rank' => $rank,
            ];
            $rank++;
        }

        // Add total ranking
        $rank = 1;
        foreach ($byTotal as $item) {
            if ($item->id === null || !isset($combined[$item->id])) {
                $rank++;
                continue;
            }
            $combined[$item->id]->rank += $rank;
            $rank++;
        }

        // Sort by combined rank (lower = better)
        usort($combined, fn($a, $b) => $a->rank <=> $b->rank);

        return array_slice($combined, 0, $limit);
    }
}
