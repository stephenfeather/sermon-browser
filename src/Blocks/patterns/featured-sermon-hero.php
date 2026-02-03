<?php

/**
 * Featured Sermon Hero Pattern
 *
 * A hero section displaying the latest sermon prominently.
 *
 * @package SermonBrowser\Blocks\Patterns
 * @since 0.8.0
 */

declare(strict_types=1);

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

register_block_pattern(
    'sermon-browser/featured-sermon-hero',
    [
        'title'       => __('Featured Sermon Hero', 'sermon-browser'),
        'description' => __('A hero section displaying the latest sermon with full details.', 'sermon-browser'),
        'categories'  => ['sermon-browser'],
        'keywords'    => ['sermon', 'hero', 'featured', 'latest'],
        'blockTypes'  => ['sermon-browser/single-sermon'],
        'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#f8f9fa"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#f8f9fa;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--30)">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__('Latest Sermon', 'sermon-browser') . '</h2>
<!-- /wp:heading -->

<!-- wp:sermon-browser/single-sermon {"useLatest":true,"showMedia":true,"showDescription":true} /-->
</div>
<!-- /wp:group -->',
    ]
);
