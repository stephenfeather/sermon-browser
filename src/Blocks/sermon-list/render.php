<?php

/**
 * Server-side rendering of the sermon-browser/sermon-list block.
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

$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 10;
$preacher_id = isset($attributes['preacherId']) ? (int) $attributes['preacherId'] : 0;
$series_id = isset($attributes['seriesId']) ? (int) $attributes['seriesId'] : 0;
$service_id = isset($attributes['serviceId']) ? (int) $attributes['serviceId'] : 0;
$show_filters = isset($attributes['showFilters']) ? (bool) $attributes['showFilters'] : true;
$filter_type = isset($attributes['filterType']) ? sanitize_text_field($attributes['filterType']) : 'dropdown';
$show_pagination = isset($attributes['showPagination']) ? (bool) $attributes['showPagination'] : true;
$order_by = isset($attributes['orderBy']) ? sanitize_text_field($attributes['orderBy']) : 'datetime';
$order = isset($attributes['order']) ? sanitize_text_field($attributes['order']) : 'desc';

// Build filter from attributes and URL params (URL params override block defaults).
$filter = [
    'preacher' => isset($_REQUEST['preacher']) ? (int) $_REQUEST['preacher'] : $preacher_id,
    'series' => isset($_REQUEST['series']) ? (int) $_REQUEST['series'] : $series_id,
    'service' => isset($_REQUEST['service']) ? (int) $_REQUEST['service'] : $service_id,
    'book' => isset($_REQUEST['book']) ? sanitize_text_field($_REQUEST['book']) : '',
    'date' => isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : '',
    'enddate' => isset($_REQUEST['enddate']) ? sanitize_text_field($_REQUEST['enddate']) : '',
    'tag' => isset($_REQUEST['stag']) ? sanitize_text_field($_REQUEST['stag']) : '',
    'title' => isset($_REQUEST['title']) ? sanitize_text_field($_REQUEST['title']) : '',
];

// Map orderBy attribute to database column.
$order_by_map = [
    'datetime' => 'm.datetime',
    'title' => 'm.title',
    'preacher' => 'p.name',
    'series' => 'ss.name',
];
$sort_by = $order_by_map[$order_by] ?? 'm.datetime';

$sort_order = [
    'by' => $sort_by,
    'dir' => strtolower($order) === 'asc' ? 'asc' : 'desc',
];

// Get current page from URL.
$page = isset($_REQUEST['pagenum']) ? (int) $_REQUEST['pagenum'] : 1;
$page = max(1, $page);

// Fetch sermons.
global $record_count;
$sermons = sb_get_sermons($filter, $sort_order, $page, $limit);

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-sermon-list',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($show_filters && $filter_type !== 'none') : ?>
        <div class="sb-sermon-list__filters">
            <form method="get" class="sb-sermon-list__filter-form">
                <?php
                // Preserve page ID if using query string permalinks.
                if (isset($_GET['page_id'])) {
                    echo '<input type="hidden" name="page_id" value="' . esc_attr($_GET['page_id']) . '">';
                }
                ?>

                <?php if ($filter_type === 'dropdown') : ?>
                    <select name="preacher" class="sb-sermon-list__filter-select">
                        <option value=""><?php esc_html_e('All Preachers', 'sermon-browser'); ?></option>
                        <?php
                        $preachers = \SermonBrowser\Facades\Preacher::findAll();
                        foreach ($preachers as $preacher) :
                            ?>
                            <option value="<?php echo esc_attr($preacher->id); ?>" <?php selected($filter['preacher'], $preacher->id); ?>>
                                <?php echo esc_html($preacher->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="series" class="sb-sermon-list__filter-select">
                        <option value=""><?php esc_html_e('All Series', 'sermon-browser'); ?></option>
                        <?php
                        $all_series = \SermonBrowser\Facades\Series::findAll();
                        foreach ($all_series as $s) :
                            ?>
                            <option value="<?php echo esc_attr($s->id); ?>" <?php selected($filter['series'], $s->id); ?>>
                                <?php echo esc_html($s->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="service" class="sb-sermon-list__filter-select">
                        <option value=""><?php esc_html_e('All Services', 'sermon-browser'); ?></option>
                        <?php
                        $services = \SermonBrowser\Facades\Service::findAll();
                        foreach ($services as $service) :
                            ?>
                            <option value="<?php echo esc_attr($service->id); ?>" <?php selected($filter['service'], $service->id); ?>>
                                <?php echo esc_html($service->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="sb-sermon-list__filter-submit">
                        <?php esc_html_e('Filter', 'sermon-browser'); ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($sermons)) : ?>
        <p class="sb-sermon-list__no-results">
            <?php esc_html_e('No sermons found.', 'sermon-browser'); ?>
        </p>
    <?php else : ?>
        <div class="sb-sermon-list__results">
            <p class="sb-sermon-list__count">
                <?php
                printf(
                    /* translators: %d: number of sermons */
                    _n('%d sermon found', '%d sermons found', $record_count, 'sermon-browser'),
                    $record_count
                );
                ?>
            </p>

            <ul class="sb-sermon-list__items">
                <?php foreach ($sermons as $sermon) : ?>
                    <li class="sb-sermon-list__item">
                        <article class="sb-sermon-list__sermon">
                            <h3 class="sb-sermon-list__sermon-title">
                                <a href="<?php echo esc_url(sb_build_url(['sermon_id' => $sermon->id])); ?>">
                                    <?php echo esc_html($sermon->title); ?>
                                </a>
                            </h3>

                            <div class="sb-sermon-list__sermon-meta">
                                <?php if (!empty($sermon->datetime)) : ?>
                                    <span class="sb-sermon-list__sermon-date">
                                        <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($sermon->preacher)) : ?>
                                    <span class="sb-sermon-list__sermon-preacher">
                                        <?php echo esc_html($sermon->preacher); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($sermon->series)) : ?>
                                    <span class="sb-sermon-list__sermon-series">
                                        <?php echo esc_html($sermon->series); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($sermon->description)) : ?>
                                <div class="sb-sermon-list__sermon-excerpt">
                                    <?php echo esc_html(wp_trim_words($sermon->description, 30)); ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($show_pagination && $record_count > $limit) : ?>
            <nav class="sb-sermon-list__pagination">
                <?php
                $total_pages = (int) ceil($record_count / $limit);
                $base_url = remove_query_arg('pagenum');

                // Previous link.
                if ($page > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('pagenum', $page - 1, $base_url)); ?>" class="sb-sermon-list__pagination-prev">
                        &laquo; <?php esc_html_e('Previous', 'sermon-browser'); ?>
                    </a>
                <?php endif; ?>

                <span class="sb-sermon-list__pagination-info">
                    <?php
                    printf(
                        /* translators: 1: current page, 2: total pages */
                        esc_html__('Page %1$d of %2$d', 'sermon-browser'),
                        $page,
                        $total_pages
                    );
                    ?>
                </span>

                <?php // Next link.
                if ($page < $total_pages) : ?>
                    <a href="<?php echo esc_url(add_query_arg('pagenum', $page + 1, $base_url)); ?>" class="sb-sermon-list__pagination-next">
                        <?php esc_html_e('Next', 'sermon-browser'); ?> &raquo;
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
