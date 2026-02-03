<?php

/**
 * Server-side rendering of the sermon-browser/series-grid block.
 *
 * @package sermon-browser
 * @since 0.7.0
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

$columns = isset($attributes['columns']) ? (int) $attributes['columns'] : 3;
$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 12;
$show_count = isset($attributes['showCount']) ? (bool) $attributes['showCount'] : true;
$show_description = isset($attributes['showDescription']) ? (bool) $attributes['showDescription'] : false;
$order_by = isset($attributes['orderBy']) ? sanitize_text_field($attributes['orderBy']) : 'name';
$order = isset($attributes['order']) ? sanitize_text_field($attributes['order']) : 'asc';

// Fetch series with sermon counts.
$all_series = \SermonBrowser\Facades\Series::findAllWithSermonCount();

if (empty($all_series)) {
    return;
}

// Sort series.
usort($all_series, function ($a, $b) use ($order_by, $order) {
    if ($order_by === 'count') {
        $comparison = ($a->sermon_count ?? 0) - ($b->sermon_count ?? 0);
    } else {
        $comparison = strcasecmp($a->name ?? '', $b->name ?? '');
    }
    return $order === 'desc' ? -$comparison : $comparison;
});

// Apply limit.
if ($limit > 0) {
    $all_series = array_slice($all_series, 0, $limit);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-series-grid',
    'style' => '--sb-series-grid-columns: ' . (int) $columns . ';',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="sb-series-grid__grid">
        <?php foreach ($all_series as $series) : ?>
            <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['series' => $series->id])); ?>" class="sb-series-grid__card">
                <h3 class="sb-series-grid__title"><?php echo esc_html($series->name); ?></h3>
                <?php if ($show_count) : ?>
                    <span class="sb-series-grid__count">
                        <?php
                        printf(
                            /* translators: %d: number of sermons */
                            _n('%d sermon', '%d sermons', (int) ($series->sermon_count ?? 0), 'sermon-browser'),
                            (int) ($series->sermon_count ?? 0)
                        );
                        ?>
                    </span>
                <?php endif; ?>
                <?php if ($show_description && !empty($series->description)) : ?>
                    <p class="sb-series-grid__description"><?php echo esc_html($series->description); ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
