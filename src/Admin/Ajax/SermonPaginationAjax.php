<?php

/**
 * Sermon Pagination AJAX Handler.
 *
 * Handles AJAX requests for sermon list pagination with filtering.
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Facades\Sermon;

/**
 * Class SermonPaginationAjax
 *
 * AJAX handler for sermon list pagination.
 */
class SermonPaginationAjax extends AjaxHandler
{
    /**
     * {@inheritDoc}
     */
    protected string $nonceAction = 'sb_sermon_nonce';

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        add_action('wp_ajax_sb_sermon_list', [$this, 'list']);
    }

    /**
     * Get paginated sermon list with filters.
     *
     * Expected POST data:
     * - page: int (required) - Current page number (1-based).
     * - per_page: int (optional) - Items per page (defaults to sermons_per_page option).
     * - title: string (optional) - Filter by title.
     * - preacher: int (optional) - Filter by preacher ID.
     * - series: int (optional) - Filter by series ID.
     *
     * @return void
     */
    public function list(): void
    {
        $this->verifyRequest();

        $page = max(1, $this->getPostInt('page', 1));
        $perPage = $this->getPostInt('per_page', 0);

        if ($perPage <= 0) {
            $perPage = (int) (OptionsManager::get('sermons_per_page') ?: 10);
        }

        $offset = ($page - 1) * $perPage;

        // Build filter array
        $filter = [];

        $title = $this->getPostString('title');
        if (!empty($title)) {
            $filter['title'] = $title;
        }

        $preacherId = $this->getPostInt('preacher');
        if ($preacherId > 0) {
            $filter['preacher_id'] = $preacherId;
        }

        $seriesId = $this->getPostInt('series');
        if ($seriesId > 0) {
            $filter['series_id'] = $seriesId;
        }

        // Get sermons and total count
        $sermons = Sermon::findAllWithRelations($filter, $perPage, $offset);
        $total = Sermon::countFiltered($filter);

        // Format sermon data for JSON response
        $items = array_map(function ($sermon) {
            return [
                'id' => (int) $sermon->id,
                'title' => stripslashes($sermon->title ?? ''),
                'preacher_name' => stripslashes($sermon->preacher_name ?? ''),
                'series_name' => stripslashes($sermon->series_name ?? ''),
                'service_name' => stripslashes($sermon->service_name ?? ''),
                'datetime' => $sermon->datetime,
                'formatted_date' => $this->formatDate($sermon->datetime),
                'stats' => sb_sermon_stats((int) $sermon->id),
                'edit_url' => admin_url("admin.php?page=sermon-browser/new_sermon.php&mid={$sermon->id}"),
                'delete_url' => admin_url("admin.php?page=sermon-browser/sermon.php&mid={$sermon->id}"),
                'view_url' => sb_display_url() . sb_query_char(true) . 'sermon_id=' . $sermon->id,
                'can_edit' => current_user_can('edit_posts'),
            ];
        }, $sermons);

        $this->success([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'has_prev' => $page > 1,
            'has_next' => ($offset + $perPage) < $total,
        ]);
    }

    /**
     * Format a datetime for display.
     *
     * @param string $datetime The datetime string.
     * @return string Formatted date or 'Unknown'.
     */
    private function formatDate(string $datetime): string
    {
        if ($datetime === '1970-01-01 00:00:00' || empty($datetime)) {
            return __('Unknown', 'sermon-browser');
        }

        return wp_date('d M y', strtotime($datetime));
    }
}
