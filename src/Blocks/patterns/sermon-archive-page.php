<?php

/**
 * Sermon Archive Page Pattern
 *
 * A complete sermon archive layout with filters and list.
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
    'sermon-browser/sermon-archive-page',
    [
        'title'       => __('Sermon Archive Page', 'sermon-browser'),
        'description' => __('A complete sermon archive with filters above the sermon list.', 'sermon-browser'),
        'categories'  => ['sermon-browser'],
        'keywords'    => ['sermon', 'archive', 'list', 'filter'],
        'blockTypes'  => ['sermon-browser/sermon-filters', 'sermon-browser/sermon-list'],
        'content'     => '<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide">
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">' . esc_html__('Sermon Archive', 'sermon-browser') . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__('Browse our collection of sermons. Use the filters below to find specific topics, speakers, or series.', 'sermon-browser') . '</p>
<!-- /wp:paragraph -->

<!-- wp:sermon-browser/sermon-filters {"filterType":"oneclick","showPreachers":true,"showSeries":true,"showServices":true,"showBooks":false,"layout":"horizontal"} /-->

<!-- wp:sermon-browser/sermon-list {"showFilters":false,"perPage":12,"showPagination":true} /-->
</div>
<!-- /wp:group -->',
    ]
);
