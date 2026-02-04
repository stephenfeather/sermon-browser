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

// New filter display options.
$show_books = isset($attributes['showBooks']) ? (bool) $attributes['showBooks'] : false;
$show_tags = isset($attributes['showTags']) ? (bool) $attributes['showTags'] : false;
$show_date_range = isset($attributes['showDateRange']) ? (bool) $attributes['showDateRange'] : false;
$show_search = isset($attributes['showSearch']) ? (bool) $attributes['showSearch'] : false;

// Default filter values from block attributes.
$default_book = isset($attributes['bookId']) ? sanitize_text_field($attributes['bookId']) : '';
$default_tag = isset($attributes['tagSlug']) ? sanitize_text_field($attributes['tagSlug']) : '';
$default_start_date = isset($attributes['startDate']) ? sanitize_text_field($attributes['startDate']) : '';
$default_end_date = isset($attributes['endDate']) ? sanitize_text_field($attributes['endDate']) : '';

// Build filter from attributes and URL params (URL params override block defaults).
$filter = [
    'preacher' => isset($_REQUEST['preacher']) ? (int) $_REQUEST['preacher'] : $preacher_id,
    'series' => isset($_REQUEST['series']) ? (int) $_REQUEST['series'] : $series_id,
    'service' => isset($_REQUEST['service']) ? (int) $_REQUEST['service'] : $service_id,
    'book' => isset($_REQUEST['book']) ? sanitize_text_field($_REQUEST['book']) : $default_book,
    'date' => isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : $default_start_date,
    'enddate' => isset($_REQUEST['enddate']) ? sanitize_text_field($_REQUEST['enddate']) : $default_end_date,
    'tag' => isset($_REQUEST['stag']) ? sanitize_text_field($_REQUEST['stag']) : $default_tag,
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
    <?php if ($show_filters && $filter_type !== 'none') :
        // Fetch filter data.
        $preachers = \SermonBrowser\Facades\Preacher::findAllWithSermonCount();
        $all_series = \SermonBrowser\Facades\Series::findAllWithSermonCount();
        $services = \SermonBrowser\Facades\Service::findAllSorted();
        $books = $show_books ? \SermonBrowser\Facades\Book::findAllWithSermonCount() : [];
        $tags = $show_tags ? \SermonBrowser\Facades\Tag::findAllWithSermonCount(0) : [];

        // Get Bible book translations.
        $eng_books = \SermonBrowser\Config\Defaults::get('eng_bible_books');
        $translated_books = \SermonBrowser\Config\Defaults::get('bible_books');
        $book_translations = array_combine($eng_books, $translated_books);

        // Helper function to build filter URL.
        $build_url = function (string $param, $value): string {
            $params = $_GET;
            if ($value) {
                $params[$param] = $value;
            } else {
                unset($params[$param]);
            }
            unset($params['pagenum']);
            return add_query_arg($params, get_permalink());
        };

        // Helper function to clear filter URL.
        $clear_url = function (string $param): string {
            $params = $_GET;
            unset($params[$param]);
            unset($params['pagenum']);
            return add_query_arg($params, get_permalink());
        };

        // Oneclick render function.
        $render_oneclick_group = function (
            string $id,
            string $label,
            array $items,
            string $param,
            $current,
            callable $build_url,
            callable $clear_url,
            string $value_field = 'id',
            string $label_field = 'name',
            array $translations = []
        ) {
            if (empty($items)) {
                return;
            }
            ?>
            <div class="sb-sermon-list__filter-group sb-sermon-list__filter-group--<?php echo esc_attr($id); ?>">
                <span class="sb-sermon-list__filter-label"><?php echo esc_html($label); ?>:</span>
                <div class="sb-sermon-list__filter-buttons">
                    <?php if ($current) : ?>
                        <a href="<?php echo esc_url($clear_url($param)); ?>" class="sb-sermon-list__filter-button sb-sermon-list__filter-button--clear">
                            <?php esc_html_e('All', 'sermon-browser'); ?>
                        </a>
                    <?php endif; ?>
                    <?php foreach ($items as $item) :
                        $value = is_object($item) ? ($item->$value_field ?? '') : ($item[$value_field] ?? '');
                        $item_label = is_object($item) ? ($item->$label_field ?? '') : ($item[$label_field] ?? '');
                        $count = is_object($item) ? ($item->sermon_count ?? $item->count ?? null) : ($item['sermon_count'] ?? $item['count'] ?? null);

                        if (!empty($translations) && isset($translations[$item_label])) {
                            $item_label = $translations[$item_label];
                        }

                        $is_active = ($current == $value);
                        $button_class = 'sb-sermon-list__filter-button' . ($is_active ? ' sb-sermon-list__filter-button--active' : '');
                        $url = $is_active ? $clear_url($param) : $build_url($param, $value);
                        ?>
                        <a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr($button_class); ?>">
                            <?php echo esc_html($item_label); ?>
                            <?php if ($count !== null) : ?>
                                <span class="sb-sermon-list__filter-count">(<?php echo (int) $count; ?>)</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        };
    ?>
        <div class="sb-sermon-list__filters sb-sermon-list__filters--<?php echo esc_attr($filter_type); ?>">
            <?php if ($filter_type === 'dropdown') : ?>
                <form method="get" class="sb-sermon-list__filter-form">
                    <?php
                    // Preserve page ID if using query string permalinks.
                    if (isset($_GET['page_id'])) {
                        echo '<input type="hidden" name="page_id" value="' . esc_attr($_GET['page_id']) . '">';
                    }
                    ?>

                    <select name="preacher" class="sb-sermon-list__filter-select">
                        <option value=""><?php esc_html_e('All Preachers', 'sermon-browser'); ?></option>
                        <?php foreach ($preachers as $preacher) : ?>
                            <option value="<?php echo esc_attr($preacher->id); ?>" <?php selected($filter['preacher'], $preacher->id); ?>>
                                <?php echo esc_html($preacher->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="series" class="sb-sermon-list__filter-select">
                        <option value=""><?php esc_html_e('All Series', 'sermon-browser'); ?></option>
                        <?php foreach ($all_series as $s) : ?>
                            <option value="<?php echo esc_attr($s->id); ?>" <?php selected($filter['series'], $s->id); ?>>
                                <?php echo esc_html($s->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="service" class="sb-sermon-list__filter-select">
                        <option value=""><?php esc_html_e('All Services', 'sermon-browser'); ?></option>
                        <?php foreach ($services as $service) : ?>
                            <option value="<?php echo esc_attr($service->id); ?>" <?php selected($filter['service'], $service->id); ?>>
                                <?php echo esc_html($service->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($show_books && !empty($books)) : ?>
                        <select name="book" class="sb-sermon-list__filter-select">
                            <option value=""><?php esc_html_e('All Books', 'sermon-browser'); ?></option>
                            <?php foreach ($books as $book) :
                                $book_label = isset($book_translations[$book->name]) ? $book_translations[$book->name] : $book->name;
                                ?>
                                <option value="<?php echo esc_attr($book->name); ?>" <?php selected($filter['book'], $book->name); ?>>
                                    <?php echo esc_html($book_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <?php if ($show_tags && !empty($tags)) : ?>
                        <select name="stag" class="sb-sermon-list__filter-select">
                            <option value=""><?php esc_html_e('All Tags', 'sermon-browser'); ?></option>
                            <?php foreach ($tags as $tag) : ?>
                                <option value="<?php echo esc_attr($tag->name); ?>" <?php selected($filter['tag'], $tag->name); ?>>
                                    <?php echo esc_html($tag->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <?php if ($show_date_range) : ?>
                        <input type="date" name="date" value="<?php echo esc_attr($filter['date']); ?>"
                               class="sb-sermon-list__filter-date" placeholder="<?php esc_attr_e('Start date', 'sermon-browser'); ?>">
                        <input type="date" name="enddate" value="<?php echo esc_attr($filter['enddate']); ?>"
                               class="sb-sermon-list__filter-date" placeholder="<?php esc_attr_e('End date', 'sermon-browser'); ?>">
                    <?php endif; ?>

                    <?php if ($show_search) : ?>
                        <input type="text" name="title" value="<?php echo esc_attr($filter['title']); ?>"
                               placeholder="<?php esc_attr_e('Search titles...', 'sermon-browser'); ?>"
                               class="sb-sermon-list__filter-search">
                    <?php endif; ?>

                    <button type="submit" class="sb-sermon-list__filter-submit">
                        <?php esc_html_e('Filter', 'sermon-browser'); ?>
                    </button>
                </form>

            <?php elseif ($filter_type === 'oneclick') : ?>
                <div class="sb-sermon-list__filter-groups">
                    <?php
                    $render_oneclick_group(
                        'preachers',
                        __('Preachers', 'sermon-browser'),
                        $preachers,
                        'preacher',
                        $filter['preacher'],
                        $build_url,
                        $clear_url
                    );

                    $render_oneclick_group(
                        'series',
                        __('Series', 'sermon-browser'),
                        $all_series,
                        'series',
                        $filter['series'],
                        $build_url,
                        $clear_url
                    );

                    $render_oneclick_group(
                        'services',
                        __('Services', 'sermon-browser'),
                        $services,
                        'service',
                        $filter['service'],
                        $build_url,
                        $clear_url
                    );

                    if ($show_books) {
                        $render_oneclick_group(
                            'books',
                            __('Books', 'sermon-browser'),
                            $books,
                            'book',
                            $filter['book'],
                            $build_url,
                            $clear_url,
                            'name',
                            'name',
                            $book_translations
                        );
                    }

                    if ($show_tags) {
                        $render_oneclick_group(
                            'tags',
                            __('Tags', 'sermon-browser'),
                            $tags,
                            'stag',
                            $filter['tag'],
                            $build_url,
                            $clear_url,
                            'name',
                            'name'
                        );
                    }
                    ?>
                </div>

                <?php if ($show_date_range || $show_search) : ?>
                    <form method="get" class="sb-sermon-list__filter-form sb-sermon-list__filter-form--secondary">
                        <?php
                        // Preserve existing filters.
                        foreach ($_GET as $key => $value) {
                            if (!in_array($key, ['date', 'enddate', 'title', 'pagenum'], true)) {
                                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                            }
                        }
                        ?>

                        <?php if ($show_date_range) : ?>
                            <div class="sb-sermon-list__filter-date-range">
                                <input type="date" name="date" value="<?php echo esc_attr($filter['date']); ?>"
                                       class="sb-sermon-list__filter-date">
                                <span class="sb-sermon-list__filter-date-separator"><?php esc_html_e('to', 'sermon-browser'); ?></span>
                                <input type="date" name="enddate" value="<?php echo esc_attr($filter['enddate']); ?>"
                                       class="sb-sermon-list__filter-date">
                            </div>
                        <?php endif; ?>

                        <?php if ($show_search) : ?>
                            <input type="text" name="title" value="<?php echo esc_attr($filter['title']); ?>"
                                   placeholder="<?php esc_attr_e('Search titles...', 'sermon-browser'); ?>"
                                   class="sb-sermon-list__filter-search">
                        <?php endif; ?>

                        <button type="submit" class="sb-sermon-list__filter-submit">
                            <?php esc_html_e('Apply', 'sermon-browser'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
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
                                <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id])); ?>">
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
