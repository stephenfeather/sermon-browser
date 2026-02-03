<?php

/**
 * Server-side rendering of the sermon-browser/popular-sermons block.
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
$content_type = isset($attributes['contentType']) ? sanitize_text_field($attributes['contentType']) : 'sermons';
$show_count = isset($attributes['showCount']) ? (bool) $attributes['showCount'] : false;
$layout = isset($attributes['layout']) ? sanitize_text_field($attributes['layout']) : 'list';

// Get popular content using PopularityService.
$popularity_service = new \SermonBrowser\Repositories\PopularityService();

$items = [];
switch ($content_type) {
    case 'sermons':
        $items = $popularity_service->getPopularSermons($limit);
        break;
    case 'series':
        $items = $popularity_service->getPopularSeries($limit);
        break;
    case 'preachers':
        $items = $popularity_service->getPopularPreachers($limit);
        break;
}

if (empty($items)) {
    return;
}

$class_name = sprintf(
    'sb-popular-content sb-popular-content--%s sb-popular-content--%s',
    esc_attr($layout),
    esc_attr($content_type)
);
$wrapper_attributes = get_block_wrapper_attributes(['class' => $class_name]);

/**
 * Get the URL for an item based on content type.
 *
 * @param object $item The item object.
 * @param string $type The content type.
 * @return string The URL.
 */
$get_item_url = function ($item, $type) {
    switch ($type) {
        case 'sermons':
            return \SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $item->id], true);
        case 'series':
            return \SermonBrowser\Frontend\UrlBuilder::build(['series' => $item->id]);
        case 'preachers':
            return \SermonBrowser\Frontend\UrlBuilder::build(['preacher' => $item->id]);
        default:
            return '';
    }
};

/**
 * Get the display name for an item.
 *
 * @param object $item The item object.
 * @param string $type The content type.
 * @return string The display name.
 */
$get_item_name = function ($item, $type) {
    return $type === 'sermons' ? stripslashes($item->title) : stripslashes($item->name);
};

/**
 * Get the count for an item (hits or sermon count).
 *
 * @param object $item The item object.
 * @param string $type The content type.
 * @return int The count.
 */
$get_item_count = function ($item, $type) {
    if ($type === 'sermons') {
        return (int) ($item->hits ?? 0);
    }
    // For series and preachers, we could add sermon_count if available
    return 0;
};

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($layout === 'grid') : ?>
        <div class="sb-popular-content__grid">
            <?php foreach ($items as $item) :
                $item_url = $get_item_url($item, $content_type);
                $item_name = $get_item_name($item, $content_type);
                ?>
                <a href="<?php echo esc_url($item_url); ?>" class="sb-popular-content__card">
                    <span class="sb-popular-content__name">
                        <?php echo esc_html($item_name); ?>
                    </span>
                    <?php if ($show_count) : ?>
                        <span class="sb-popular-content__count">
                            <?php echo (int) $get_item_count($item, $content_type); ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <ul class="sb-popular-content__list">
            <?php foreach ($items as $item) :
                $item_url = $get_item_url($item, $content_type);
                $item_name = $get_item_name($item, $content_type);
                ?>
                <li class="sb-popular-content__item">
                    <a href="<?php echo esc_url($item_url); ?>" class="sb-popular-content__link">
                        <span class="sb-popular-content__name">
                            <?php echo esc_html($item_name); ?>
                        </span>
                        <?php if ($show_count) : ?>
                            <span class="sb-popular-content__count">
                                (<?php echo (int) $get_item_count($item, $content_type); ?>)
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
