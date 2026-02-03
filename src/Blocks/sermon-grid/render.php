<?php

/**
 * Server-side rendering of the sermon-browser/sermon-grid block.
 *
 * @package sermon-browser
 * @since 0.8.0
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

$layout = isset($attributes['layout']) ? sanitize_text_field($attributes['layout']) : 'grid';
$columns = isset($attributes['columns']) ? (int) $attributes['columns'] : 3;
$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 6;
$show_thumbnails = isset($attributes['showThumbnails']) ? (bool) $attributes['showThumbnails'] : true;
$show_excerpt = isset($attributes['showExcerpt']) ? (bool) $attributes['showExcerpt'] : true;
$excerpt_length = isset($attributes['excerptLength']) ? (int) $attributes['excerptLength'] : 20;
$show_preacher = isset($attributes['showPreacher']) ? (bool) $attributes['showPreacher'] : true;
$show_date = isset($attributes['showDate']) ? (bool) $attributes['showDate'] : true;
$show_series = isset($attributes['showSeries']) ? (bool) $attributes['showSeries'] : false;
$preacher_id = isset($attributes['preacherId']) ? (int) $attributes['preacherId'] : 0;
$series_id = isset($attributes['seriesId']) ? (int) $attributes['seriesId'] : 0;
$order_by = isset($attributes['orderBy']) ? sanitize_text_field($attributes['orderBy']) : 'datetime';
$order = isset($attributes['order']) ? sanitize_text_field($attributes['order']) : 'desc';

// Build filter array.
$filter = [];
if ($preacher_id > 0) {
    $filter['preacher'] = $preacher_id;
}
if ($series_id > 0) {
    $filter['series'] = $series_id;
}

// Map orderBy to database column.
$order_column = $order_by === 'title' ? 'm.title' : 'm.datetime';

// Fetch sermons using the existing function.
$sermons = sb_get_sermons($filter, ['by' => $order_column, 'dir' => $order], 1, $limit);

if (empty($sermons)) {
    return;
}

// Get upload URL for thumbnails.
$upload_url = \SermonBrowser\Config\OptionsManager::get('upload_url');

/**
 * Truncate text to a specific word count.
 *
 * @param string $text  The text to truncate.
 * @param int    $limit Word limit.
 * @return string Truncated text.
 */
$truncate_text = function (string $text, int $limit): string {
    $words = explode(' ', $text);
    if (count($words) <= $limit) {
        return $text;
    }
    return implode(' ', array_slice($words, 0, $limit)) . '...';
};

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-sermon-grid sb-sermon-grid--' . esc_attr($layout),
    'style' => '--sb-sermon-grid-columns: ' . (int) $columns . ';',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="sb-sermon-grid__grid">
        <?php foreach ((array) $sermons as $sermon) : ?>
            <?php $sermon_url = \SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id]); ?>
            <div class="sb-sermon-grid__card">
                <?php if ($show_thumbnails) :
                    // Check for sermon thumbnail - could be from series or custom field.
                    $thumbnail_url = '';
                    if (!empty($sermon->series_id)) {
                        $series_data = \SermonBrowser\Facades\Series::findById((int) $sermon->series_id);
                        if ($series_data && !empty($series_data->image)) {
                            $thumbnail_url = $upload_url . $series_data->image;
                        }
                    }
                    if ($thumbnail_url) : ?>
                        <img
                            src="<?php echo esc_url($thumbnail_url); ?>"
                            alt="<?php echo esc_attr(stripslashes($sermon->title)); ?>"
                            class="sb-sermon-grid__thumbnail"
                        />
                    <?php endif; ?>
                <?php endif; ?>

                <h3 class="sb-sermon-grid__title">
                    <a href="<?php echo esc_url($sermon_url); ?>">
                        <?php echo esc_html(stripslashes($sermon->title)); ?>
                    </a>
                </h3>

                <?php if ($show_preacher || $show_date || $show_series) : ?>
                    <div class="sb-sermon-grid__meta">
                        <?php if ($show_preacher && !empty($sermon->preacher)) : ?>
                            <span class="sb-sermon-grid__preacher">
                                <?php echo esc_html(stripslashes($sermon->preacher)); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($show_date && !empty($sermon->datetime)) : ?>
                            <span class="sb-sermon-grid__date">
                                <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($show_series && !empty($sermon->series)) :
                            $series_url = \SermonBrowser\Frontend\UrlBuilder::build(['series' => $sermon->series_id]);
                            ?>
                            <span class="sb-sermon-grid__series">
                                <a href="<?php echo esc_url($series_url); ?>">
                                    <?php echo esc_html(stripslashes($sermon->series)); ?>
                                </a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_excerpt && !empty($sermon->description)) : ?>
                    <p class="sb-sermon-grid__excerpt">
                        <?php echo esc_html($truncate_text(stripslashes($sermon->description), $excerpt_length)); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
