<?php

/**
 * Exception thrown when wp_die is called in tests.
 *
 * @package SermonBrowser\Tests\Exceptions
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Exceptions;

use RuntimeException;

/**
 * Exception to simulate wp_die() behavior in tests.
 */
class WpDieException extends RuntimeException
{
}
