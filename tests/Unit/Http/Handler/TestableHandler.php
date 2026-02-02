<?php

/**
 * Testable handler class for HandlerTrait coverage testing.
 *
 * @package SermonBrowser\Tests\Unit\Http\Handler
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http\Handler;

use SermonBrowser\Http\Handler\HandlerTrait;

/**
 * Concrete class that uses HandlerTrait for testing.
 */
class TestableHandler
{
    use HandlerTrait;

    /**
     * Public wrapper for protected notFound method.
     *
     * @param string $message The error message.
     */
    public static function testNotFound(string $message): void
    {
        self::notFound($message);
    }

    /**
     * Public wrapper for protected urlNotFound method.
     *
     * @param string $message The error message.
     */
    public static function testUrlNotFound(string $message): void
    {
        self::urlNotFound($message);
    }

    /**
     * Public wrapper for protected incrementDownloadCount method.
     *
     * @param string $name The file name.
     */
    public static function testIncrementDownloadCount(string $name): void
    {
        self::incrementDownloadCount($name);
    }
}
