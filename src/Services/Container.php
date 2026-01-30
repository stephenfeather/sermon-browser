<?php
/**
 * Service Container.
 *
 * Simple singleton container for dependency injection.
 *
 * @package SermonBrowser\Services
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Services;

use SermonBrowser\Repositories\SermonRepository;
use SermonBrowser\Repositories\PreacherRepository;
use SermonBrowser\Repositories\SeriesRepository;
use SermonBrowser\Repositories\ServiceRepository;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Repositories\TagRepository;

/**
 * Class Container
 *
 * Provides lazy-loaded access to all repositories.
 */
class Container
{
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Cached repository instances.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Private constructor for singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the Sermon repository.
     *
     * @return SermonRepository
     */
    public function sermons(): SermonRepository
    {
        return $this->resolve(SermonRepository::class);
    }

    /**
     * Get the Preacher repository.
     *
     * @return PreacherRepository
     */
    public function preachers(): PreacherRepository
    {
        return $this->resolve(PreacherRepository::class);
    }

    /**
     * Get the Series repository.
     *
     * @return SeriesRepository
     */
    public function series(): SeriesRepository
    {
        return $this->resolve(SeriesRepository::class);
    }

    /**
     * Get the Service repository.
     *
     * @return ServiceRepository
     */
    public function services(): ServiceRepository
    {
        return $this->resolve(ServiceRepository::class);
    }

    /**
     * Get the File repository.
     *
     * @return FileRepository
     */
    public function files(): FileRepository
    {
        return $this->resolve(FileRepository::class);
    }

    /**
     * Get the Tag repository.
     *
     * @return TagRepository
     */
    public function tags(): TagRepository
    {
        return $this->resolve(TagRepository::class);
    }

    /**
     * Resolve a repository instance with lazy loading.
     *
     * @template T of object
     * @param class-string<T> $class The repository class name.
     * @return T
     */
    private function resolve(string $class): object
    {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = new $class();
        }

        return $this->instances[$class];
    }

    /**
     * Reset the container (primarily for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Set a custom instance (primarily for testing).
     *
     * @param string $class The repository class name.
     * @param object $instance The instance to use.
     * @return void
     */
    public function set(string $class, object $instance): void
    {
        $this->instances[$class] = $instance;
    }
}
