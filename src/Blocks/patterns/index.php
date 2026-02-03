<?php

/**
 * Block Patterns Registration
 *
 * Registers all Sermon Browser block patterns and the pattern category.
 *
 * @package SermonBrowser\Blocks\Patterns
 * @since 0.8.0
 */

declare(strict_types=1);

namespace SermonBrowser\Blocks\Patterns;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register block patterns and category.
 *
 * @return void
 */
function register_patterns(): void
{
    // Register the Sermon Browser pattern category.
    register_block_pattern_category('sermon-browser', [
        'label' => __('Sermon Browser', 'sermon-browser'),
    ]);

    // Include individual pattern files.
    $pattern_files = [
        'featured-sermon-hero',
        'sermon-archive-page',
        'preacher-spotlight',
        'popular-this-week',
        'tag-cloud-sidebar',
    ];

    foreach ($pattern_files as $pattern) {
        $file = __DIR__ . '/' . $pattern . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Hook into init to register patterns.
add_action('init', __NAMESPACE__ . '\\register_patterns');
