<?php

/**
 * Server-side rendering of the sermon-browser/recent-sermons block.
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

$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 5;
$show_preacher = isset($attributes['showPreacher']) ? (bool) $attributes['showPreacher'] : true;
$show_date = isset($attributes['showDate']) ? (bool) $attributes['showDate'] : true;
$show_series = isset($attributes['showSeries']) ? (bool) $attributes['showSeries'] : false;
$show_passage = isset($attributes['showPassage']) ? (bool) $attributes['showPassage'] : false;
$preacher_id = isset($attributes['preacherId']) ? (int) $attributes['preacherId'] : 0;
$series_id = isset($attributes['seriesId']) ? (int) $attributes['seriesId'] : 0;
$service_id = isset($attributes['serviceId']) ? (int) $attributes['serviceId'] : 0;

// Build filter array.
$filter = [];
if ($preacher_id > 0) {
    $filter['preacher'] = $preacher_id;
}
if ($series_id > 0) {
    $filter['series'] = $series_id;
}
if ($service_id > 0) {
    $filter['service'] = $service_id;
}

// Fetch sermons using the existing function.
$sermons = sb_get_sermons($filter, ['by' => 'm.datetime', 'dir' => 'desc'], 1, $limit);

if (empty($sermons)) {
    return;
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-recent-sermons',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <ul class="sb-recent-sermons__list">
        <?php foreach ((array) $sermons as $sermon) : ?>
            <li class="sb-recent-sermons__item">
                <?php $sermon_url = \SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id], true); ?>
                <a href="<?php echo esc_url($sermon_url); ?>" class="sb-recent-sermons__link">
                    <span class="sb-recent-sermons__title">
                        <?php echo esc_html(stripslashes($sermon->title)); ?>
                    </span>
                </a>
                <?php if ($show_passage && !empty($sermon->start) && !empty($sermon->end)) :
                    $start_data = unserialize($sermon->start, ['allowed_classes' => false]);
                    $end_data = unserialize($sermon->end, ['allowed_classes' => false]);
                    if ($start_data && $end_data) : ?>
                        <span class="sb-recent-sermons__passage">
                            (<?php echo esc_html(sb_get_books($start_data[0], $end_data[0])); ?>)
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($show_preacher && !empty($sermon->preacher)) : ?>
                    <span class="sb-recent-sermons__preacher">
                        <?php echo esc_html__('by', 'sermon-browser'); ?>
                        <a href="<?php sb_print_preacher_link($sermon); ?>">
                            <?php echo esc_html(stripslashes($sermon->preacher)); ?>
                        </a>
                    </span>
                <?php endif; ?>
                <?php if ($show_series && !empty($sermon->series)) :
                    $series_url = \SermonBrowser\Frontend\UrlBuilder::build(['series' => $sermon->series_id]);
                    ?>
                    <span class="sb-recent-sermons__series">
                        <?php echo esc_html__('in', 'sermon-browser'); ?>
                        <a href="<?php echo esc_url($series_url); ?>">
                            <?php echo esc_html(stripslashes($sermon->series)); ?>
                        </a>
                    </span>
                <?php endif; ?>
                <?php if ($show_date) : ?>
                    <span class="sb-recent-sermons__date">
                        <?php echo esc_html__('on', 'sermon-browser'); ?>
                        <?php echo esc_html(sb_formatted_date($sermon)); ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
