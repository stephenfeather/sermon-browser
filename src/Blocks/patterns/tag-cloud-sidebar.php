<?php

/**
 * Tag Cloud Sidebar Pattern
 *
 * A sidebar widget displaying a tag cloud for browsing by topic.
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
    'sermon-browser/tag-cloud-sidebar',
    [
        'title'       => __('Tag Cloud Sidebar', 'sermon-browser'),
        'description' => __('A sidebar widget with a tag cloud for browsing sermons by topic.', 'sermon-browser'),
        'categories'  => ['sermon-browser'],
        'keywords'    => ['tag', 'cloud', 'sidebar', 'widget', 'topics'],
        'blockTypes'  => ['sermon-browser/tag-cloud'],
        'content'     => '<!-- wp:group {"className":"sidebar-widget","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}},"border":{"width":"1px","color":"#e0e0e0","radius":"4px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group sidebar-widget has-border-color" style="border-color:#e0e0e0;border-width:1px;border-radius:4px;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)">
<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|20"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__('Browse by Topic', 'sermon-browser') . '</h3>
<!-- /wp:heading -->

<!-- wp:sermon-browser/tag-cloud {"limit":20,"showCount":false,"minFontSize":12,"maxFontSize":22} /-->
</div>
<!-- /wp:group -->',
    ]
);
