<?php
/**
 * Base Facade.
 *
 * Provides static access to repository methods via __callStatic magic.
 *
 * @package SermonBrowser\Facades
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Facades;

use SermonBrowser\Services\Container;

/**
 * Abstract class Facade
 *
 * Base class for all facades. Child classes must implement getRepository().
 */
abstract class Facade
{
    /**
     * Get the repository instance for this facade.
     *
     * @return object The repository instance.
     */
    abstract protected static function getRepository(): object;

    /**
     * Get the container instance.
     *
     * @return Container
     */
    protected static function getContainer(): Container
    {
        return Container::getInstance();
    }

    /**
     * Handle dynamic static method calls.
     *
     * Proxies all static calls to the underlying repository instance.
     *
     * @param string $method The method name.
     * @param array<mixed> $arguments The method arguments.
     * @return mixed
     * @throws \BadMethodCallException If method doesn't exist on repository.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        $repository = static::getRepository();

        if (!method_exists($repository, $method)) {
            throw new \BadMethodCallException(
                sprintf('Method %s::%s does not exist.', static::class, $method)
            );
        }

        return $repository->$method(...$arguments);
    }
}
