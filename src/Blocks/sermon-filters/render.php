<?php

/**
 * Server-side rendering of the sermon-browser/sermon-filters block.
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

use SermonBrowser\Config\Defaults;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Book;
use SermonBrowser\Facades\Tag;

// Extract attributes with defaults.
$filter_type = $attributes['filterType'] ?? 'oneclick';
$show_preachers = $attributes['showPreachers'] ?? true;
$show_series = $attributes['showSeries'] ?? true;
$show_services = $attributes['showServices'] ?? true;
$show_books = $attributes['showBooks'] ?? true;
$show_tags = $attributes['showTags'] ?? false;
$show_date_range = $attributes['showDateRange'] ?? false;
$show_search = $attributes['showSearch'] ?? false;
$target_url = $attributes['targetUrl'] ?? '';
$layout = $attributes['layout'] ?? 'horizontal';

// Determine target URL (default to current page).
$base_url = !empty($target_url) ? $target_url : get_permalink();

// Get current filter values from URL.
$current_preacher = isset($_GET['preacher']) ? (int) $_GET['preacher'] : 0;
$current_series = isset($_GET['series']) ? (int) $_GET['series'] : 0;
$current_service = isset($_GET['service']) ? (int) $_GET['service'] : 0;
$current_book = isset($_GET['book']) ? sanitize_text_field($_GET['book']) : '';
$current_tag = isset($_GET['stag']) ? sanitize_text_field($_GET['stag']) : '';
$current_search = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : '';
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$current_enddate = isset($_GET['enddate']) ? sanitize_text_field($_GET['enddate']) : '';

/**
 * Helper function to build filter URL.
 *
 * @param string $param Filter parameter name.
 * @param mixed  $value Filter value.
 * @return string The built URL.
 */
$build_url = function (string $param, $value) use ($base_url): string {
    $params = $_GET;
    if ($value) {
        $params[$param] = $value;
    } else {
        unset($params[$param]);
    }
    // Reset pagination when filtering.
    unset($params['page']);
    return add_query_arg($params, $base_url);
};

/**
 * Helper function to build clear filter URL.
 *
 * @param string $param Filter parameter to remove.
 * @return string The built URL.
 */
$clear_url = function (string $param) use ($base_url): string {
    $params = $_GET;
    unset($params[$param]);
    unset($params['page']);
    return add_query_arg($params, $base_url);
};

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-sermon-filters sb-sermon-filters--' . esc_attr($layout) . ' sb-sermon-filters--' . esc_attr($filter_type),
]);

// Fetch filter data.
$preachers = $show_preachers ? Preacher::findAllWithSermonCount() : [];
$series_list = $show_series ? Series::findAllWithSermonCount() : [];
$services = $show_services ? Service::findAllSorted() : [];
$books = $show_books ? Book::findAllWithSermonCount() : [];
$tags = $show_tags ? Tag::findAllWithSermonCount(0) : [];

// Get Bible book translations.
$eng_books = Defaults::get('eng_bible_books');
$translated_books = Defaults::get('bible_books');
$book_translations = array_combine($eng_books, $translated_books);

/**
 * Render oneclick filter group.
 *
 * @param string $id       Filter ID.
 * @param string $label    Filter label.
 * @param array  $items    Filter items.
 * @param string $param    URL parameter name.
 * @param mixed  $current  Current selected value.
 * @param callable $build_url URL builder function.
 * @param callable $clear_url Clear URL builder function.
 * @param string $value_field Field to use for value.
 * @param string $label_field Field to use for label.
 * @param array  $translations Optional translations array.
 */
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

    echo '<div class="sb-sermon-filters__group" data-filter="' . esc_attr($id) . '">';
    echo '<span class="sb-sermon-filters__label">' . esc_html($label) . '</span>';
    echo '<div class="sb-sermon-filters__buttons">';

    // Add "All" button if a filter is active.
    if ($current) {
        echo '<a href="' . esc_url($clear_url($param)) . '" class="sb-sermon-filters__button sb-sermon-filters__button--clear">';
        echo esc_html__('All', 'sermon-browser');
        echo '</a>';
    }

    foreach ($items as $item) {
        $value = is_object($item) ? ($item->$value_field ?? '') : ($item[$value_field] ?? '');
        $item_label = is_object($item) ? ($item->$label_field ?? '') : ($item[$label_field] ?? '');
        $count = is_object($item) ? ($item->sermon_count ?? $item->count ?? null) : ($item['sermon_count'] ?? $item['count'] ?? null);

        // Apply translations if available.
        if (!empty($translations) && isset($translations[$item_label])) {
            $item_label = $translations[$item_label];
        }

        $is_active = ($current == $value);
        $button_class = 'sb-sermon-filters__button';
        if ($is_active) {
            $button_class .= ' sb-sermon-filters__button--active';
        }

        $url = $is_active ? $clear_url($param) : $build_url($param, $value);

        echo '<a href="' . esc_url($url) . '" class="' . esc_attr($button_class) . '">';
        echo esc_html($item_label);
        if ($count !== null) {
            echo ' <span class="sb-sermon-filters__count">(' . (int) $count . ')</span>';
        }
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
};

/**
 * Render dropdown filter group.
 *
 * @param string $id       Filter ID.
 * @param string $label    Filter label.
 * @param array  $items    Filter items.
 * @param string $param    URL parameter name.
 * @param mixed  $current  Current selected value.
 * @param callable $build_url URL builder function.
 * @param string $value_field Field to use for value.
 * @param string $label_field Field to use for label.
 * @param array  $translations Optional translations array.
 */
$render_dropdown_group = function (
    string $id,
    string $label,
    array $items,
    string $param,
    $current,
    callable $build_url,
    string $value_field = 'id',
    string $label_field = 'name',
    array $translations = []
) use ($base_url) {
    if (empty($items)) {
        return;
    }

    echo '<div class="sb-sermon-filters__group" data-filter="' . esc_attr($id) . '">';
    echo '<label class="sb-sermon-filters__label" for="sb-filter-' . esc_attr($id) . '">' . esc_html($label) . '</label>';
    echo '<select class="sb-sermon-filters__select" id="sb-filter-' . esc_attr($id) . '" name="' . esc_attr($param) . '" onchange="if(this.value){window.location.href=this.value;}">';

    // Default "All" option.
    $all_url = add_query_arg(array_diff_key($_GET, [$param => '']), $base_url);
    echo '<option value="' . esc_url($all_url) . '">' . esc_html(sprintf(__('All %s', 'sermon-browser'), $label)) . '</option>';

    foreach ($items as $item) {
        $value = is_object($item) ? ($item->$value_field ?? '') : ($item[$value_field] ?? '');
        $item_label = is_object($item) ? ($item->$label_field ?? '') : ($item[$label_field] ?? '');
        $count = is_object($item) ? ($item->sermon_count ?? $item->count ?? null) : ($item['sermon_count'] ?? $item['count'] ?? null);

        // Apply translations if available.
        if (!empty($translations) && isset($translations[$item_label])) {
            $item_label = $translations[$item_label];
        }

        $is_selected = ($current == $value);
        $url = $build_url($param, $value);

        $option_label = $item_label;
        if ($count !== null) {
            $option_label .= ' (' . (int) $count . ')';
        }

        echo '<option value="' . esc_url($url) . '"' . ($is_selected ? ' selected' : '') . '>';
        echo esc_html($option_label);
        echo '</option>';
    }

    echo '</select>';
    echo '</div>';
};

/**
 * Render search input.
 */
$render_search = function () use ($base_url, $current_search) {
    echo '<div class="sb-sermon-filters__group" data-filter="search">';
    echo '<label class="sb-sermon-filters__label" for="sb-filter-search">' . esc_html__('Search', 'sermon-browser') . '</label>';
    echo '<form class="sb-sermon-filters__search" method="get" action="' . esc_url($base_url) . '">';

    // Preserve existing filters.
    foreach ($_GET as $key => $value) {
        if ($key !== 'title' && $key !== 'page') {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
    }

    echo '<input type="text" class="sb-sermon-filters__search-input" id="sb-filter-search" name="title" value="' . esc_attr($current_search) . '" placeholder="' . esc_attr__('Search sermons...', 'sermon-browser') . '">';
    echo '<button type="submit" class="sb-sermon-filters__search-button">' . esc_html__('Search', 'sermon-browser') . '</button>';
    echo '</form>';
    echo '</div>';
};

/**
 * Render date range filter.
 */
$render_date_range = function () use ($base_url, $current_date, $current_enddate) {
    echo '<div class="sb-sermon-filters__group" data-filter="date">';
    echo '<span class="sb-sermon-filters__label">' . esc_html__('Date Range', 'sermon-browser') . '</span>';
    echo '<form class="sb-sermon-filters__date-range" method="get" action="' . esc_url($base_url) . '">';

    // Preserve existing filters.
    foreach ($_GET as $key => $value) {
        if ($key !== 'date' && $key !== 'enddate' && $key !== 'page') {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
    }

    echo '<input type="date" class="sb-sermon-filters__date-input" name="date" value="' . esc_attr($current_date) . '">';
    echo '<span class="sb-sermon-filters__date-separator">' . esc_html__('to', 'sermon-browser') . '</span>';
    echo '<input type="date" class="sb-sermon-filters__date-input" name="enddate" value="' . esc_attr($current_enddate) . '">';
    echo '<button type="submit" class="sb-sermon-filters__date-button">' . esc_html__('Apply', 'sermon-browser') . '</button>';
    echo '</form>';
    echo '</div>';
};

// Determine which render function to use based on filter type.
$render_group = ($filter_type === 'dropdown') ? $render_dropdown_group : $render_oneclick_group;

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($show_preachers && !empty($preachers)) : ?>
        <?php $render_group(
            'preachers',
            __('Preachers', 'sermon-browser'),
            $preachers,
            'preacher',
            $current_preacher,
            $build_url,
            $clear_url
        ); ?>
    <?php endif; ?>

    <?php if ($show_series && !empty($series_list)) : ?>
        <?php $render_group(
            'series',
            __('Series', 'sermon-browser'),
            $series_list,
            'series',
            $current_series,
            $build_url,
            $clear_url
        ); ?>
    <?php endif; ?>

    <?php if ($show_services && !empty($services)) : ?>
        <?php $render_group(
            'services',
            __('Services', 'sermon-browser'),
            $services,
            'service',
            $current_service,
            $build_url,
            $clear_url
        ); ?>
    <?php endif; ?>

    <?php if ($show_books && !empty($books)) : ?>
        <?php $render_group(
            'books',
            __('Books', 'sermon-browser'),
            $books,
            'book',
            $current_book,
            $build_url,
            $clear_url,
            'name',
            'name',
            $book_translations
        ); ?>
    <?php endif; ?>

    <?php if ($show_tags && !empty($tags)) : ?>
        <?php $render_group(
            'tags',
            __('Tags', 'sermon-browser'),
            $tags,
            'stag',
            $current_tag,
            $build_url,
            $clear_url,
            'name',
            'name'
        ); ?>
    <?php endif; ?>

    <?php if ($show_date_range) : ?>
        <?php $render_date_range(); ?>
    <?php endif; ?>

    <?php if ($show_search) : ?>
        <?php $render_search(); ?>
    <?php endif; ?>
</div>
