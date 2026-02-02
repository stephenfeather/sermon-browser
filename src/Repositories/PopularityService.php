<?php

/**
 * Popularity Service.
 *
 * Handles popularity calculations and rankings for sermons, series, and preachers.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

/**
 * Class PopularityService
 *
 * Calculates popularity rankings based on download counts.
 */
class PopularityService
{
    /**
     * Database instance.
     *
     * @var \wpdb
     */
    private \wpdb $db;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Get the most popular sermon by download count.
     *
     * @return object|null The most popular sermon data or null.
     */
    public function getMostPopularSermon(): ?object
    {
        $stuffTable = $this->db->prefix . 'sb_stuff';
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT s.title, f.sermon_id, SUM(f.count) AS c
                FROM {$stuffTable} f
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
        $stuffTable = $this->db->prefix . 'sb_stuff';
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = $this->db->prepare(
            "SELECT sermons.id, sermons.title, SUM(stuff.count) AS total
             FROM {$stuffTable} AS stuff
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
        $stuffTable = $this->db->prefix . 'sb_stuff';
        $sermonsTable = $this->db->prefix . 'sb_sermons';
        $seriesTable = $this->db->prefix . 'sb_series';

        // Get series ranked by average downloads
        $byAverage = $this->db->get_results(
            "SELECT series.id, series.name, AVG(stuff.count) AS average
             FROM {$stuffTable} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$seriesTable} AS series ON sermons.series_id = series.id
             GROUP BY series.id
             ORDER BY average DESC"
        );

        // Get series ranked by total downloads
        $byTotal = $this->db->get_results(
            "SELECT series.id, SUM(stuff.count) AS total
             FROM {$stuffTable} AS stuff
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
        $stuffTable = $this->db->prefix . 'sb_stuff';
        $sermonsTable = $this->db->prefix . 'sb_sermons';
        $preachersTable = $this->db->prefix . 'sb_preachers';

        // Get preachers ranked by average downloads
        $byAverage = $this->db->get_results(
            "SELECT preachers.id, preachers.name, AVG(stuff.count) AS average
             FROM {$stuffTable} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$preachersTable} AS preachers ON sermons.preacher_id = preachers.id
             GROUP BY preachers.id
             ORDER BY average DESC"
        );

        // Get preachers ranked by total downloads
        $byTotal = $this->db->get_results(
            "SELECT preachers.id, SUM(stuff.count) AS total
             FROM {$stuffTable} AS stuff
             LEFT JOIN {$sermonsTable} AS sermons ON stuff.sermon_id = sermons.id
             LEFT JOIN {$preachersTable} AS preachers ON sermons.preacher_id = preachers.id
             GROUP BY preachers.id
             ORDER BY total DESC"
        );

        return $this->combineRankings($byAverage, $byTotal, $limit);
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
