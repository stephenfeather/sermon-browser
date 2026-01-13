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
}
