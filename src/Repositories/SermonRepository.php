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
    protected string $tableSuffix = 'sb_sermons';

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
        if (!empty($filter['preacher_id']) && (int) $filter['preacher_id'] !== 0) {
            $sql .= $this->db->prepare(' AND s.preacher_id = %d', (int) $filter['preacher_id']);
        }

        if (!empty($filter['series_id']) && (int) $filter['series_id'] !== 0) {
            $sql .= $this->db->prepare(' AND s.series_id = %d', (int) $filter['series_id']);
        }

        if (!empty($filter['service_id']) && (int) $filter['service_id'] !== 0) {
            $sql .= $this->db->prepare(' AND s.service_id = %d', (int) $filter['service_id']);
        }

        $sql .= ' ORDER BY s.datetime DESC';

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
     * Get previous sermon (by date).
     *
     * @param int $id Current sermon ID.
     * @return object|null The previous sermon or null.
     */
    public function findPrevious(int $id): ?object
    {
        $sermon = $this->find($id);

        if ($sermon === null) {
            return null;
        }

        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE datetime < %s ORDER BY datetime DESC LIMIT 1",
            $sermon->datetime
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * Get next sermon (by date).
     *
     * @param int $id Current sermon ID.
     * @return object|null The next sermon or null.
     */
    public function findNext(int $id): ?object
    {
        $sermon = $this->find($id);

        if ($sermon === null) {
            return null;
        }

        $table = $this->getTableName();

        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE datetime > %s ORDER BY datetime ASC LIMIT 1",
            $sermon->datetime
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }
}
