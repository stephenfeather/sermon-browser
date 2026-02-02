<?php

/**
 * Sermon Repository.
 *
 * Handles all database operations for sermons.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

use SermonBrowser\Constants;

/**
 * Class SermonRepository
 *
 * Repository for the sb_sermons table.
 */
class SermonRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = Constants::TABLE_SERMONS;

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'title',
        'preacher_id',
        'datetime',
        'service_id',
        'series_id',
        'start',
        'end',
        'description',
        'time',
        'override',
        'page_id',
    ];

    /**
     * Find sermons by preacher ID.
     *
     * @param int $preacherId The preacher ID.
     * @param int $limit Maximum results.
     * @return array<object> Array of sermons.
     */
    public function findByPreacher(int $preacherId, int $limit = 0): array
    {
        return $this->findAll(['preacher_id' => $preacherId], $limit, 0, 'datetime', 'DESC');
    }

    /**
     * Find sermons by series ID.
     *
     * @param int $seriesId The series ID.
     * @param int $limit Maximum results.
     * @return array<object> Array of sermons.
     */
    public function findBySeries(int $seriesId, int $limit = 0): array
    {
        return $this->findAll(['series_id' => $seriesId], $limit, 0, 'datetime', 'DESC');
    }

    /**
     * Find sermons by service ID.
     *
     * @param int $serviceId The service ID.
     * @param int $limit Maximum results.
     * @return array<object> Array of sermons.
     */
    public function findByService(int $serviceId, int $limit = 0): array
    {
        return $this->findAll(['service_id' => $serviceId], $limit, 0, 'datetime', 'DESC');
    }

    /**
     * Find recent sermons.
     *
     * @param int $limit Maximum results.
     * @return array<object> Array of recent sermons.
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->findAll([], $limit, 0, 'datetime', 'DESC');
    }

    /**
     * Find sermons by date range.
     *
     * @param string $startDate Start date (Y-m-d format).
     * @param string $endDate End date (Y-m-d format).
     * @param int $limit Maximum results.
     * @return array<object> Array of sermons.
     */
    public function findByDateRange(string $startDate, string $endDate, int $limit = 0): array
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE datetime >= %s AND datetime <= %s ORDER BY datetime DESC",
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59'
        );

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d', $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Search sermons by title.
     *
     * @param string $search The search term.
     * @param int $limit Maximum results.
     * @return array<object> Array of matching sermons.
     */
    public function searchByTitle(string $search, int $limit = 0): array
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE title LIKE %s ORDER BY datetime DESC",
            '%' . $this->db->esc_like($search) . '%'
        );

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d', $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get sermon with related data (preacher, series, service).
     *
     * @param int $id The sermon ID.
     * @return object|null The sermon with related data.
     */
    public function findWithRelations(int $id): ?object
    {
        $table = $this->getTableName();
        $preachersTable = $this->db->prefix . 'sb_preachers';
        $seriesTable = $this->db->prefix . 'sb_series';
        $servicesTable = $this->db->prefix . 'sb_services';

        $sql = $this->db->prepare(
            "SELECT
                s.*,
                p.name AS preacher_name,
                p.description AS preacher_description,
                p.image AS preacher_image,
                ser.name AS series_name,
                srv.name AS service_name,
                srv.time AS service_time
            FROM {$table} s
            LEFT JOIN {$preachersTable} p ON s.preacher_id = p.id
            LEFT JOIN {$seriesTable} ser ON s.series_id = ser.id
            LEFT JOIN {$servicesTable} srv ON s.service_id = srv.id
            WHERE s.id = %d",
            $id
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * Get all sermons with related data for listing.
     *
     * @param array<string, mixed> $filter Filter criteria.
     * @param int $limit Maximum results.
     * @param int $offset Number to skip.
     * @return array<object> Array of sermons with relations.
     */
    public function findAllWithRelations(array $filter = [], int $limit = 0, int $offset = 0): array
    {
        $table = $this->getTableName();
        $preachersTable = $this->db->prefix . 'sb_preachers';
        $seriesTable = $this->db->prefix . 'sb_series';
        $servicesTable = $this->db->prefix . 'sb_services';

        $sql = "SELECT
                s.*,
                p.name AS preacher_name,
                ser.name AS series_name,
                srv.name AS service_name
            FROM {$table} s
            LEFT JOIN {$preachersTable} p ON s.preacher_id = p.id
            LEFT JOIN {$seriesTable} ser ON s.series_id = ser.id
            LEFT JOIN {$servicesTable} srv ON s.service_id = srv.id
            WHERE 1=1";

        // Apply filters
        if (!empty($filter['title'])) {
            $sql .= $this->db->prepare(
                ' AND s.title LIKE %s',
                '%' . $this->db->esc_like($filter['title']) . '%'
            );
        }

        if (!empty($filter['preacher_id']) && (int) $filter['preacher_id'] !== 0) {
            $sql .= $this->db->prepare(' AND s.preacher_id = %d', (int) $filter['preacher_id']);
        }

        if (!empty($filter['series_id']) && (int) $filter['series_id'] !== 0) {
            $sql .= $this->db->prepare(' AND s.series_id = %d', (int) $filter['series_id']);
        }

        if (!empty($filter['service_id']) && (int) $filter['service_id'] !== 0) {
            $sql .= $this->db->prepare(' AND s.service_id = %d', (int) $filter['service_id']);
        }

        $sql .= ' ORDER BY s.datetime DESC, srv.time DESC';

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d OFFSET %d', $limit, $offset);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Count sermons matching filter criteria.
     *
     * @param array<string, mixed> $filter Filter criteria.
     * @return int The count.
     */
    public function countFiltered(array $filter = []): int
    {
        $table = $this->getTableName();

        $sql = "SELECT COUNT(*) FROM {$table} WHERE 1=1";

        if (!empty($filter['title'])) {
            $sql .= $this->db->prepare(
                ' AND title LIKE %s',
                '%' . $this->db->esc_like($filter['title']) . '%'
            );
        }

        if (!empty($filter['preacher_id']) && (int) $filter['preacher_id'] !== 0) {
            $sql .= $this->db->prepare(' AND preacher_id = %d', (int) $filter['preacher_id']);
        }

        if (!empty($filter['series_id']) && (int) $filter['series_id'] !== 0) {
            $sql .= $this->db->prepare(' AND series_id = %d', (int) $filter['series_id']);
        }

        if (!empty($filter['service_id']) && (int) $filter['service_id'] !== 0) {
            $sql .= $this->db->prepare(' AND service_id = %d', (int) $filter['service_id']);
        }

        return (int) $this->db->get_var($sql);
    }

    /**
     * Get distinct years that have sermons.
     *
     * @return array<int> Array of years.
     */
    public function getYears(): array
    {
        $table = $this->getTableName();

        $results = $this->db->get_col(
            "SELECT DISTINCT YEAR(datetime) as year FROM {$table} ORDER BY year DESC"
        );

        return array_map('intval', $results);
    }

    /**
     * Get sermons for a specific year and month.
     *
     * @param int $year The year.
     * @param int $month The month (1-12).
     * @return array<object> Array of sermons.
     */
    public function findByYearMonth(int $year, int $month): array
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE YEAR(datetime) = %d AND MONTH(datetime) = %d ORDER BY datetime DESC",
            $year,
            $month
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get sermons for admin listing with related names and filtering.
     *
     * @param array<string, mixed> $filter Filter criteria (title, preacher_id, series_id).
     * @param int $limit Maximum results.
     * @param int $offset Number to skip.
     * @return array<object> Array of sermons with pname, sname, ssname columns.
     */
    public function findForAdminListFiltered(array $filter = [], int $limit = 0, int $offset = 0): array
    {
        $table = $this->getTableName();
        $preachersTable = $this->db->prefix . 'sb_preachers';
        $seriesTable = $this->db->prefix . 'sb_series';
        $servicesTable = $this->db->prefix . 'sb_services';

        $sql = "SELECT m.id, m.title, m.datetime, p.name as pname, s.name as sname, ss.name as ssname
                FROM {$table} as m
                LEFT JOIN {$preachersTable} as p ON m.preacher_id = p.id
                LEFT JOIN {$servicesTable} as s ON m.service_id = s.id
                LEFT JOIN {$seriesTable} as ss ON m.series_id = ss.id
                WHERE 1=1";

        // Apply filters
        if (!empty($filter['title'])) {
            $sql .= $this->db->prepare(
                ' AND m.title LIKE %s',
                '%' . $this->db->esc_like($filter['title']) . '%'
            );
        }

        if (!empty($filter['preacher_id']) && (int) $filter['preacher_id'] !== 0) {
            $sql .= $this->db->prepare(' AND m.preacher_id = %d', (int) $filter['preacher_id']);
        }

        if (!empty($filter['series_id']) && (int) $filter['series_id'] !== 0) {
            $sql .= $this->db->prepare(' AND m.series_id = %d', (int) $filter['series_id']);
        }

        $sql .= ' ORDER BY m.datetime DESC, s.time DESC';

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d, %d', $offset, $limit);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get next sermon by date (excluding same day).
     *
     * Used for sermon navigation links. Compares by DATE only,
     * not exact datetime, and excludes the current sermon.
     *
     * @param string $datetime The current sermon's datetime.
     * @param int $excludeId The current sermon ID to exclude.
     * @return object|null The next sermon or null.
     */
    public function findNextByDate(string $datetime, int $excludeId): ?object
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT id, title FROM {$table}
             WHERE DATE(datetime) > DATE(%s) AND id <> %d
             ORDER BY datetime ASC
             LIMIT 1",
            $datetime,
            $excludeId
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * Get previous sermon by date (excluding same day).
     *
     * Used for sermon navigation links. Compares by DATE only,
     * not exact datetime, and excludes the current sermon.
     *
     * @param string $datetime The current sermon's datetime.
     * @param int $excludeId The current sermon ID to exclude.
     * @return object|null The previous sermon or null.
     */
    public function findPreviousByDate(string $datetime, int $excludeId): ?object
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT id, title FROM {$table}
             WHERE DATE(datetime) < DATE(%s) AND id <> %d
             ORDER BY datetime DESC
             LIMIT 1",
            $datetime,
            $excludeId
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * Get sermons preached on the same day.
     *
     * Used for "also preached that day" links.
     *
     * @param string $datetime The current sermon's datetime.
     * @param int $excludeId The current sermon ID to exclude.
     * @return array<object> Array of sermons on the same day.
     */
    public function findSameDay(string $datetime, int $excludeId): array
    {
        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT id, title FROM {$table}
             WHERE DATE(datetime) = DATE(%s) AND id <> %d",
            $datetime,
            $excludeId
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get sermon with related data using legacy property names for templates.
     *
     * Returns a sermon object with property names matching the legacy
     * template system expectations: pid, preacher, image, sid, service,
     * ssid, series (instead of preacher_id, preacher_name, etc.)
     *
     * @param int $id The sermon ID.
     * @return object|null The sermon with related data using legacy aliases.
     */
    public function findForTemplate(int $id): ?object
    {
        $table = $this->getTableName();
        $preachersTable = $this->db->prefix . 'sb_preachers';
        $seriesTable = $this->db->prefix . 'sb_series';
        $servicesTable = $this->db->prefix . 'sb_services';

        $sql = $this->db->prepare(
            "SELECT
                m.id,
                m.title,
                m.datetime,
                m.start,
                m.end,
                m.description,
                p.id AS pid,
                p.name AS preacher,
                p.image AS image,
                p.description AS preacher_description,
                s.id AS sid,
                s.name AS service,
                ss.id AS ssid,
                ss.name AS series
            FROM {$table} m
            LEFT JOIN {$preachersTable} p ON m.preacher_id = p.id
            LEFT JOIN {$servicesTable} s ON m.service_id = s.id
            LEFT JOIN {$seriesTable} ss ON m.series_id = ss.id
            WHERE m.id = %d",
            $id
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * Get date components for specific sermon IDs (oneclick filter).
     *
     * Returns year, month, day for each sermon's datetime.
     * Result objects have: year, month, day.
     *
     * @param array<int> $sermonIds Array of sermon IDs to get dates for.
     * @return array<object> Array of date objects.
     */
    public function findDatesForIds(array $sermonIds): array
    {
        if (empty($sermonIds)) {
            return [];
        }

        $table = $this->getTableName();

        $placeholders = implode(', ', array_fill(0, count($sermonIds), '%d'));
        $sql = $this->db->prepare(
            "SELECT YEAR(datetime) AS year, MONTH(datetime) AS month, DAY(datetime) AS day
             FROM {$table}
             WHERE id IN ({$placeholders})
             ORDER BY datetime ASC",
            ...$sermonIds
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Find sermons for frontend listing with full filtering and pagination.
     *
     * Complex query that joins all related tables (preachers, services, series,
     * books, tags, stuff) and supports comprehensive filtering for the frontend
     * sermon list display.
     *
     * Filter options:
     * - title: Search in title, description, and tag names
     * - preacher: Preacher ID
     * - date: Start date (inclusive)
     * - enddate: End date (inclusive)
     * - series: Series ID
     * - service: Service ID
     * - book: Bible book name
     * - tag: Tag name (partial match)
     * - id: Specific sermon ID
     *
     * @param array<string, mixed> $filter Filter criteria.
     * @param array<string, string> $order Order criteria ('by' => column, 'dir' => ASC/DESC).
     * @param int $page Page number (1-based).
     * @param int $limit Results per page.
     * @param bool $hideEmpty Only show sermons with attached files.
     * @return array{items: array<object>, total: int} Results with total count.
     */
    public function findForFrontendListing(
        array $filter = [],
        array $order = [],
        int $page = 1,
        int $limit = 0,
        bool $hideEmpty = false
    ): array {
        $table = $this->getTableName();
        $preachersTable = $this->db->prefix . 'sb_preachers';
        $servicesTable = $this->db->prefix . 'sb_services';
        $seriesTable = $this->db->prefix . 'sb_series';
        $booksSermonTable = $this->db->prefix . 'sb_books_sermons';
        $booksTable = $this->db->prefix . 'sb_books';
        $sermonsTagsTable = $this->db->prefix . 'sb_sermons_tags';
        $tagsTable = $this->db->prefix . 'sb_tags';
        $stuffTable = $this->db->prefix . 'sb_stuff';

        // Default filter values
        $defaultFilter = [
            'title' => '',
            'preacher' => 0,
            'date' => '',
            'enddate' => '',
            'series' => 0,
            'service' => 0,
            'book' => '',
            'tag' => '',
            'id' => '',
        ];
        $filter = array_merge($defaultFilter, $filter);

        // Default and validate order
        $defaultOrder = ['by' => 'm.datetime', 'dir' => 'desc'];
        $order = array_merge($defaultOrder, $order);

        $orderDir = strtolower($order['dir']);
        if ($orderDir !== 'desc' && $orderDir !== 'asc') {
            $orderDir = 'desc';
        }

        $validSortColumns = [
            'm.id', 'm.title', 'm.datetime', 'm.start', 'm.end',
            'p.id', 'p.name', 's.id', 's.name', 'ss.id', 'ss.name'
        ];
        $orderBy = in_array($order['by'], $validSortColumns, true)
            ? $order['by']
            : 'm.datetime';

        // Special handling for book ordering
        if ($orderBy === 'b.id') {
            $orderBy = "b.id {$orderDir}, bs.chapter {$orderDir}, bs.verse";
        }

        // Build conditions using helper method
        $filterResult = $this->buildFrontendFilterConditions($filter, $hideEmpty);
        $conditions = $filterResult['conditions'];
        $bookJoinCondition = $filterResult['bookJoinCondition'];

        // Calculate offset
        $offset = $limit * ($page - 1);

        // Build the query
        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT
                m.id, m.title, m.description, m.datetime, m.time, m.start, m.end,
                p.id as pid, p.name as preacher, p.description as preacher_description, p.image,
                s.id as sid, s.name as service,
                ss.id as ssid, ss.name as series
            FROM {$table} as m
            LEFT JOIN {$preachersTable} as p ON m.preacher_id = p.id
            LEFT JOIN {$servicesTable} as s ON m.service_id = s.id
            LEFT JOIN {$seriesTable} as ss ON m.series_id = ss.id
            LEFT JOIN {$booksSermonTable} as bs ON bs.sermon_id = m.id{$bookJoinCondition}
            LEFT JOIN {$booksTable} as b ON bs.book_name = b.name
            LEFT JOIN {$sermonsTagsTable} as st ON st.sermon_id = m.id
            LEFT JOIN {$tagsTable} as t ON t.id = st.tag_id
            LEFT JOIN {$stuffTable} as stuff ON stuff.sermon_id = m.id
            WHERE {$conditions}
            ORDER BY {$orderBy} {$orderDir}
            LIMIT {$offset}, {$limit}";

        // Execute query
        $this->db->query('SET SQL_BIG_SELECTS=1');
        $results = $this->db->get_results($sql);
        $total = (int) $this->db->get_var("SELECT FOUND_ROWS()");

        return [
            'items' => is_array($results) ? $results : [],
            'total' => $total,
        ];
    }

    /**
     * Build filter conditions for frontend listing query.
     *
     * @param array<string, mixed> $filter Filter criteria.
     * @param bool $hideEmpty Only show sermons with attached files.
     * @return array{conditions: string, bookJoinCondition: string} SQL conditions.
     */
    private function buildFrontendFilterConditions(array $filter, bool $hideEmpty): array
    {
        $conditions = '1=1 ';
        $bookJoinCondition = '';

        if ($filter['title'] !== '') {
            $search = '%' . $this->db->esc_like($filter['title']) . '%';
            $conditions .= $this->db->prepare(
                " AND (m.title LIKE %s OR m.description LIKE %s OR t.name LIKE %s)",
                $search,
                $search,
                $search
            );
        }

        if ((int) $filter['preacher'] !== 0) {
            $conditions .= $this->db->prepare(' AND m.preacher_id = %d', (int) $filter['preacher']);
        }

        if ($filter['date'] !== '') {
            $conditions .= $this->db->prepare(' AND m.datetime >= %s', $filter['date']);
        }

        if ($filter['enddate'] !== '') {
            $conditions .= $this->db->prepare(' AND m.datetime <= %s', $filter['enddate']);
        }

        if ((int) $filter['series'] !== 0) {
            $conditions .= $this->db->prepare(' AND m.series_id = %d', (int) $filter['series']);
        }

        if ((int) $filter['service'] !== 0) {
            $conditions .= $this->db->prepare(' AND m.service_id = %d', (int) $filter['service']);
        }

        if ($filter['book'] !== '') {
            $conditions .= $this->db->prepare(' AND bs.book_name = %s', $filter['book']);
        } else {
            $bookJoinCondition = " AND bs.order = 0 AND bs.type = 'start'";
        }

        if ($filter['tag'] !== '') {
            $conditions .= $this->db->prepare(
                " AND t.name LIKE %s",
                '%' . $this->db->esc_like($filter['tag']) . '%'
            );
        }

        if ($filter['id'] !== '') {
            $conditions .= $this->db->prepare(' AND m.id = %s', $filter['id']);
        }

        if ($hideEmpty) {
            $conditions .= " AND stuff.name != ''";
        }

        return [
            'conditions' => $conditions,
            'bookJoinCondition' => $bookJoinCondition,
        ];
    }
}
