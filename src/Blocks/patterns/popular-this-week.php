<?php

/**
 * Popular This Week Pattern
 *
 * A section showcasing popular sermons from the past week.
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
    'sermon-browser/popular-this-week',
    [
        'title'       => __('Popular This Week', 'sermon-browser'),
        'description' => __('A section displaying the most popular sermons from the past week.', 'sermon-browser'),
        'categories'  => ['sermon-browser'],
        'keywords'    => ['popular', 'trending', 'week', 'sermon'],
        'blockTypes'  => ['sermon-browser/popular-sermons'],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__('Popular This Week', 'sermon-browser') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#666666"}}} -->
<p class="has-text-color" style="color:#666666">' . esc_html__('Discover what others are listening to this week.', 'sermon-browser') . '</p>
<!-- /wp:paragraph -->

<!-- wp:sermon-browser/popular-sermons {"limit":5,"contentType":"sermons","timePeriod":"week","showCount":true} /-->
</div>
<!-- /wp:group -->',
    ]
);
