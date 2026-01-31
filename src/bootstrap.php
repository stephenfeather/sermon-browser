<?php

/**
 * SermonBrowser Bootstrap.
 *
 * Global helper functions for the SermonBrowser plugin.
 * This file is autoloaded via Composer's "files" array.
 *
 * @package SermonBrowser
 * @since 0.6.0
 */

use SermonBrowser\Services\Container;

/**
 * Global accessor for the service container.
 *
 * @return Container
 */
function sb_container(): Container
{
    return Container::getInstance();
}
