<?php
/**
 * Abstract Repository.
 *
 * Base class for all repository implementations providing common
 * database operations via WordPress $wpdb.
 *
 * @package SermonBrowser\Repositories
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Repositories;

use SermonBrowser\Contracts\RepositoryInterface;

/**
 * Abstract class AbstractRepository
 *
 * Provides common CRUD operations for all repositories.
 * Child classes must define $tableSuffix (e.g., 'sb_sermons').
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    protected \wpdb $db;

    /**
     * Table name suffix (without prefix).
     * Must be set by child classes.
     *
     * @var string
     */
    protected string $tableSuffix = '';

    /**
     * Primary key column name.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Allowed columns for this table.
     * Used for validation and sanitization.
     * Must be set by child classes.
     *
     * @var array<string>
     */
    protected array $allowedColumns = [];

    /**
     * Constructor.
     *
     * @param \wpdb|null $db Optional database instance for testing.
     */
    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
    }

    /**
     * {@inheritDoc}
     */
    public function getTableName(): string
    {
        return $this->db->prefix . $this->tableSuffix;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?object
    {
        $table = $this->getTableName();
        $sql = $this->db->prepare(
            "SELECT * FROM {$table} WHERE {$this->primaryKey} = %d",
            $id
        );

        $result = $this->db->get_row($sql);

        return $result ?: null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(
        array $criteria = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = 'id',
        string $order = 'ASC'
    ): array {
        $table = $this->getTableName();

        // Validate order direction
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        // Validate orderBy column
        $orderBy = $this->sanitizeColumnName($orderBy) ?: $this->primaryKey;

        // Build WHERE clause
        $where = $this->buildWhereClause($criteria);

        $sql = "SELECT * FROM {$table} {$where} ORDER BY {$orderBy} {$order}";

        if ($limit > 0) {
            $sql .= $this->db->prepare(' LIMIT %d OFFSET %d', $limit, $offset);
        }

        $results = $this->db->get_results($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $criteria = []): int
    {
        $table = $this->getTableName();

        $where = $this->buildWhereClause($criteria);
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";

        return (int) $this->db->get_var($sql);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): int
    {
        $table = $this->getTableName();

        // Filter to allowed columns only
        $data = $this->filterColumns($data);

        if (empty($data)) {
            return 0;
        }

        $this->db->insert($table, $data);

        return (int) $this->db->insert_id;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): bool
    {
        $table = $this->getTableName();

        // Filter to allowed columns only
        $data = $this->filterColumns($data);

        if (empty($data)) {
            return false;
        }

        $result = $this->db->update(
            $table,
            $data,
            [$this->primaryKey => $id]
        );

        return $result !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $table = $this->getTableName();

        $result = $this->db->delete(
            $table,
            [$this->primaryKey => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Build a WHERE clause from criteria array.
     *
     * @param array<string, mixed> $criteria Key-value pairs.
     * @return string The WHERE clause (or empty string).
     */
    protected function buildWhereClause(array $criteria): string
    {
        if (empty($criteria)) {
            return '';
        }

        $conditions = [];

        foreach ($criteria as $column => $value) {
            $column = $this->sanitizeColumnName($column);

            if ($column === null) {
                continue;
            }

            if (is_null($value)) {
                $conditions[] = "{$column} IS NULL";
            } elseif (is_int($value)) {
                $conditions[] = $this->db->prepare("{$column} = %d", $value);
            } elseif (is_float($value)) {
                $conditions[] = $this->db->prepare("{$column} = %f", $value);
            } elseif (is_array($value)) {
                // Handle IN clause
                $placeholders = array_fill(0, count($value), '%s');
                $conditions[] = $this->db->prepare(
                    "{$column} IN (" . implode(', ', $placeholders) . ')',
                    ...$value
                );
            } else {
                $conditions[] = $this->db->prepare("{$column} = %s", $value);
            }
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Filter data to only include allowed columns.
     *
     * @param array<string, mixed> $data The input data.
     * @return array<string, mixed> Filtered data.
     */
    protected function filterColumns(array $data): array
    {
        if (empty($this->allowedColumns)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->allowedColumns));
    }

    /**
     * Sanitize a column name.
     *
     * @param string $column The column name.
     * @return string|null The sanitized column name or null if invalid.
     */
    protected function sanitizeColumnName(string $column): ?string
    {
        // Remove any non-alphanumeric characters except underscore
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        if (empty($column)) {
            return null;
        }

        // Validate against allowed columns if defined
        if (!empty($this->allowedColumns) && !in_array($column, $this->allowedColumns, true)) {
            // Check if it's the primary key
            if ($column === $this->primaryKey) {
                return $column;
            }
            return null;
        }

        return $column;
    }

    /**
     * Find entities by a specific column value.
     *
     * @param string $column The column to search.
     * @param mixed $value The value to match.
     * @return array<object> Array of matching entities.
     */
    public function findBy(string $column, mixed $value): array
    {
        return $this->findAll([$column => $value]);
    }

    /**
     * Find a single entity by a specific column value.
     *
     * @param string $column The column to search.
     * @param mixed $value The value to match.
     * @return object|null The entity or null if not found.
     */
    public function findOneBy(string $column, mixed $value): ?object
    {
        $results = $this->findAll([$column => $value], 1);

        return $results[0] ?? null;
    }

    /**
     * Check if an entity exists.
     *
     * @param int $id The entity's primary key.
     * @return bool True if entity exists.
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Delete transients matching a pattern.
     *
     * Utility method to bulk-delete WordPress transients by prefix pattern.
     * Consolidates $wpdb usage for transient operations in the repository layer.
     *
     * @param string $pattern The transient prefix pattern (e.g., 'sb_template_').
     * @return int Number of transients deleted.
     */
    public static function deleteTransientsByPattern(string $pattern): int
    {
        global $wpdb;

        $transients = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $wpdb->esc_like($pattern) . '%'
            )
        );

        $count = 0;
        foreach ($transients as $transient) {
            $key = str_replace('_transient_', '', $transient);
            if (delete_transient($key)) {
                $count++;
            }
        }

        return $count;
    }
}
