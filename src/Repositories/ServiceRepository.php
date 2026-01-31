<?php
/**
 * Service Repository.
 *
 * Handles all database operations for church services (e.g., Sunday Morning).
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

/**
 * Class ServiceRepository
 *
 * Repository for the sb_services table.
 */
class ServiceRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected string $tableSuffix = 'sb_services';

    /**
     * {@inheritDoc}
     */
    protected array $allowedColumns = [
        'name',
        'time',
    ];

    /**
     * Find service by name.
     *
     * @param string $name The service name.
     * @return object|null The service or null.
     */
    public function findByName(string $name): ?object
    {
        return $this->findOneBy('name', $name);
    }

    /**
     * Get all services sorted by name.
     *
     * @return array<object> Array of all services.
     */
    public function findAllSorted(): array
    {
        return $this->findAll([], 0, 0, 'name', 'ASC');
    }

    /**
     * Get all services sorted by time.
     *
     * @return array<object> Array of all services.
     */
    public function findAllByTime(): array
    {
        return $this->findAll([], 0, 0, 'time', 'ASC');
    }

    /**
     * Get services with sermon counts.
     *
     * @return array<object> Array of services with count.
     */
    public function findAllWithSermonCount(): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT s.*, COUNT(ser.id) as sermon_count
                FROM {$table} s
                LEFT JOIN {$sermonsTable} ser ON s.id = ser.service_id
                GROUP BY s.id
                ORDER BY s.name ASC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Check if service has sermons.
     *
     * @param int $id The service ID.
     * @return bool True if service has sermons.
     */
    public function hasSermons(int $id): bool
    {
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $count = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$sermonsTable} WHERE service_id = %d",
                $id
            )
        );

        return (int) $count > 0;
    }

    /**
     * Update service and cascade time changes to related sermons.
     *
     * When a service's time changes, all linked sermons (that don't have
     * override set) have their datetime adjusted by the time difference.
     *
     * @param int $id The service ID.
     * @param string $name The new service name.
     * @param string $time The new service time (HH:MM format).
     * @return bool True if update succeeded.
     */
    public function updateWithTimeShift(int $id, string $name, string $time): bool
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        // Get the old time
        $oldTime = $this->db->get_var(
            $this->db->prepare(
                "SELECT time FROM {$table} WHERE id = %d",
                $id
            )
        );

        if ($oldTime === null) {
            $oldTime = '00:00';
        }

        // Calculate time difference in seconds
        $difference = strtotime($time) - strtotime($oldTime);

        // Update the service
        $result = $this->db->update(
            $table,
            ['name' => $name, 'time' => $time],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            return false;
        }

        // Update sermon datetimes if there's a time difference
        if ($difference !== 0) {
            $this->db->query(
                $this->db->prepare(
                    "UPDATE {$sermonsTable}
                     SET datetime = DATE_ADD(datetime, INTERVAL %d SECOND)
                     WHERE override = 0 AND service_id = %d",
                    $difference,
                    $id
                )
            );
        }

        return true;
    }

    /**
     * Get the time for a service.
     *
     * @param int $id The service ID.
     * @return string|null The time or null.
     */
    public function getTime(int $id): ?string
    {
        $table = $this->getTableName();

        $time = $this->db->get_var(
            $this->db->prepare(
                "SELECT time FROM {$table} WHERE id = %d",
                $id
            )
        );

        return $time !== null ? (string) $time : null;
    }

    /**
     * Get all services with sermon counts for filter dropdowns.
     *
     * Returns services ordered by sermon count DESC.
     * Result objects have: id, name, time, count.
     *
     * @return array<object> Array of services with count.
     */
    public function findAllForFilter(): array
    {
        $table = $this->getTableName();
        $sermonsTable = $this->db->prefix . 'sb_sermons';

        $sql = "SELECT s.*, COUNT(s.id) AS count
                FROM {$table} s
                JOIN {$sermonsTable} sermons ON s.id = sermons.service_id
                GROUP BY s.id
                ORDER BY count DESC";

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Get services with counts for specific sermon IDs (oneclick filter).
     *
     * Returns services for the given sermons, with counts.
     * Result objects have: id, name, time, count.
     *
     * @param array<int> $sermonIds Array of sermon IDs to filter by.
     * @return array<object> Array of services with count.
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
            "SELECT s.*, COUNT(s.id) AS count
             FROM {$table} s
             JOIN {$sermonsTable} sermons ON s.id = sermons.service_id
             WHERE sermons.id IN ({$placeholders})
             GROUP BY s.id
             ORDER BY count DESC",
            ...$sermonIds
        );

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }
}
