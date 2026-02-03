<?php

/**
 * Preacher Spotlight Pattern
 *
 * A two-column layout featuring a preacher profile and their recent sermons.
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
    'sermon-browser/preacher-spotlight',
    [
        'title'       => __('Preacher Spotlight', 'sermon-browser'),
        'description' => __('A two-column layout with preacher profile on the left and their sermons on the right.', 'sermon-browser'),
        'categories'  => ['sermon-browser'],
        'keywords'    => ['preacher', 'speaker', 'profile', 'spotlight'],
        'blockTypes'  => ['sermon-browser/profile-block', 'sermon-browser/sermon-grid'],
        'content'     => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__('Featured Speaker', 'sermon-browser') . '</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- wp:sermon-browser/profile-block {"profileType":"preacher","showImage":true,"showBio":true,"showSermons":false,"layout":"vertical"} /-->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">' . esc_html__('Recent Sermons', 'sermon-browser') . '</h3>
<!-- /wp:heading -->

<!-- wp:sermon-browser/sermon-grid {"columns":2,"limit":4,"showThumbnails":false,"showExcerpt":true,"excerptLength":15} /-->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
    ]
);
