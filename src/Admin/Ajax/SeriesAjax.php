<?php

/**
 * Series AJAX Handler.
 *
 * Handles AJAX requests for series CRUD operations.
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

use SermonBrowser\Facades\Series;

/**
 * Class SeriesAjax
 *
 * AJAX handler for series operations.
 */
class SeriesAjax extends AjaxHandler
{
    /**
     * {@inheritDoc}
     */
    protected string $nonceAction = 'sb_series_nonce';

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        add_action('wp_ajax_sb_series_create', [$this, 'create']);
        add_action('wp_ajax_sb_series_update', [$this, 'update']);
        add_action('wp_ajax_sb_series_delete', [$this, 'delete']);
    }

    /**
     * Create a new series.
     *
     * Expected POST data:
     * - name: string (required) - The series name.
     *
     * @return void
     */
    public function create(): void
    {
        $this->verifyRequest();

        $name = $this->getPostString('name');

        if (empty($name)) {
            $this->error(__('Series name is required.', 'sermon-browser'));
        }

        $id = Series::create([
            'name' => $name,
            'page_id' => 0,
        ]);

        if ($id === 0) {
            $this->error(__('Failed to create series.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $name,
            'message' => __('Series created successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Update an existing series.
     *
     * Expected POST data:
     * - id: int (required) - The series ID.
     * - name: string (required) - The new series name.
     *
     * @return void
     */
    public function update(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');
        $name = $this->getPostString('name');

        if ($id <= 0) {
            $this->error(__('Invalid series ID.', 'sermon-browser'));
        }

        if (empty($name)) {
            $this->error(__('Series name is required.', 'sermon-browser'));
        }

        $result = Series::update($id, ['name' => $name]);

        if (!$result) {
            $this->error(__('Failed to update series.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $name,
            'message' => __('Series updated successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Delete a series.
     *
     * Expected POST data:
     * - id: int (required) - The series ID.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');

        if ($id <= 0) {
            $this->error(__('Invalid series ID.', 'sermon-browser'));
        }

        $result = Series::delete($id);

        if (!$result) {
            $this->error(__('Failed to delete series.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'message' => __('Series deleted successfully.', 'sermon-browser'),
        ]);
    }
}
