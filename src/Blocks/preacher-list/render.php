<?php

/**
 * Server-side rendering of the sermon-browser/preacher-list block.
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

$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 0;
$show_count = isset($attributes['showCount']) ? (bool) $attributes['showCount'] : true;
$order_by = isset($attributes['orderBy']) ? sanitize_text_field($attributes['orderBy']) : 'name';
$order = isset($attributes['order']) ? sanitize_text_field($attributes['order']) : 'asc';
$layout = isset($attributes['layout']) ? sanitize_text_field($attributes['layout']) : 'list';

// Fetch preachers with sermon counts.
$preachers = \SermonBrowser\Facades\Preacher::findAllWithSermonCount();

if (empty($preachers)) {
    return;
}

// Sort preachers.
usort($preachers, function ($a, $b) use ($order_by, $order) {
    if ($order_by === 'count') {
        $comparison = ($a->sermon_count ?? 0) - ($b->sermon_count ?? 0);
    } else {
        $comparison = strcasecmp($a->name ?? '', $b->name ?? '');
    }
    return $order === 'desc' ? -$comparison : $comparison;
});

// Apply limit.
if ($limit > 0) {
    $preachers = array_slice($preachers, 0, $limit);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-preacher-list sb-preacher-list--' . esc_attr($layout),
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($layout === 'grid') : ?>
        <div class="sb-preacher-list__grid">
            <?php foreach ($preachers as $preacher) : ?>
                <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['preacher' => $preacher->id])); ?>" class="sb-preacher-list__card">
                    <span class="sb-preacher-list__name"><?php echo esc_html($preacher->name); ?></span>
                    <?php if ($show_count) : ?>
                        <span class="sb-preacher-list__count">
                            <?php
                            printf(
                                /* translators: %d: number of sermons */
                                _n('%d sermon', '%d sermons', (int) ($preacher->sermon_count ?? 0), 'sermon-browser'),
                                (int) ($preacher->sermon_count ?? 0)
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <ul class="sb-preacher-list__items">
            <?php foreach ($preachers as $preacher) : ?>
                <li class="sb-preacher-list__item">
                    <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['preacher' => $preacher->id])); ?>" class="sb-preacher-list__link">
                        <span class="sb-preacher-list__name"><?php echo esc_html($preacher->name); ?></span>
                        <?php if ($show_count) : ?>
                            <span class="sb-preacher-list__count">(<?php echo (int) ($preacher->sermon_count ?? 0); ?>)</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
