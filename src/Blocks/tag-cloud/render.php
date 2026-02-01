<?php

/**
 * Server-side rendering of the sermon-browser/tag-cloud block.
 *
 * @package sermon-browser
 * @since 0.6.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Ensure we're in WordPress context.
if (!defined('ABSPATH')) {
    exit;
}

$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 0;
$min_font = isset($attributes['minFontPercent']) ? (int) $attributes['minFontPercent'] : 80;
$max_font = isset($attributes['maxFontPercent']) ? (int) $attributes['maxFontPercent'] : 150;
$show_count = isset($attributes['showCount']) ? (bool) $attributes['showCount'] : false;

// Fetch tags with sermon counts.
$tags = \SermonBrowser\Facades\Tag::findAllWithSermonCount($limit);

if (empty($tags)) {
    return;
}

// Build count array from tag objects.
$cnt = [];
foreach ($tags as $tag) {
    if (!empty($tag->name)) {
        $cnt[$tag->name] = (int) ($tag->sermon_count ?? 0);
    }
}

if (empty($cnt)) {
    return;
}

// Calculate font sizes using logarithmic scale.
$font_range = $max_font - $min_font;
$max_cnt = max($cnt);
$min_cnt = min($cnt);
$min_log = log(max($min_cnt, 1));
$max_log = log(max($max_cnt, 1));
$log_range = $max_log === $min_log ? 1 : $max_log - $min_log;

// Sort by count descending.
arsort($cnt);

// Build output.
$out = [];
foreach ($cnt as $tag_name => $count) {
    $size = $min_font + $font_range * (log(max($count, 1)) - $min_log) / $log_range;
    $link = sb_get_tag_link($tag_name);
    $tag_html = '<a class="sb-tag-cloud__tag" style="font-size:' . (int) $size . '%;" href="' . esc_url($link) . '">';
    $tag_html .= esc_html($tag_name);
    if ($show_count) {
        $tag_html .= ' <span class="sb-tag-cloud__count">(' . (int) $count . ')</span>';
    }
    $tag_html .= '</a>';
    $out[] = $tag_html;
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-tag-cloud',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="sb-tag-cloud__tags">
        <?php echo implode(' ', $out); ?>
    </div>
</div>
