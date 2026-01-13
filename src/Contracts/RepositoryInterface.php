<?php
/**
 * Repository Interface.
 *
 * Defines the contract for all repository implementations.
 * Part of the Sermon Browser v0.6.0+ architecture refactoring.
 *
 * @package SermonBrowser\Contracts
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Contracts;

/**
 * Interface RepositoryInterface
 *
 * All repositories must implement these standard CRUD operations.
 * This provides a consistent API across all data access operations.
 */
interface RepositoryInterface
{
    /**
     * Find a single entity by its primary key.
     *
     * @param int $id The entity's primary key.
     * @return object|null The entity or null if not found.
     */
    public function find(int $id): ?object;

    /**
     * Find all entities matching the given criteria.
     *
     * @param array<string, mixed> $criteria Key-value pairs for filtering.
     * @param int $limit Maximum number of results (0 = no limit).
     * @param int $offset Number of results to skip.
     * @param string $orderBy Column to order by.
     * @param string $order Sort direction ('ASC' or 'DESC').
     * @return array<object> Array of matching entities.
     */
    public function findAll(
        array $criteria = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = 'id',
        string $order = 'ASC'
    ): array;

    /**
     * Count entities matching the given criteria.
     *
     * @param array<string, mixed> $criteria Key-value pairs for filtering.
     * @return int The count of matching entities.
     */
    public function count(array $criteria = []): int;

    /**
     * Create a new entity.
     *
     * @param array<string, mixed> $data The entity data.
     * @return int The ID of the newly created entity.
     */
    public function create(array $data): int;

    /**
     * Update an existing entity.
     *
     * @param int $id The entity's primary key.
     * @param array<string, mixed> $data The data to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete an entity by its primary key.
     *
     * @param int $id The entity's primary key.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool;

    /**
     * Get the table name for this repository.
     *
     * @return string The full table name including prefix.
     */
    public function getTableName(): string;
}
