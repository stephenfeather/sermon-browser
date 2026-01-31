<?php

/**
 * Preacher AJAX Handler.
 *
 * Handles AJAX requests for preacher CRUD operations.
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

use SermonBrowser\Facades\Preacher;

/**
 * Class PreacherAjax
 *
 * AJAX handler for preacher operations.
 */
class PreacherAjax extends AjaxHandler
{
    /**
     * {@inheritDoc}
     */
    protected string $nonceAction = 'sb_preacher_nonce';

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        add_action('wp_ajax_sb_preacher_create', [$this, 'create']);
        add_action('wp_ajax_sb_preacher_update', [$this, 'update']);
        add_action('wp_ajax_sb_preacher_delete', [$this, 'delete']);
    }

    /**
     * Create a new preacher.
     *
     * Expected POST data:
     * - name: string (required) - The preacher name.
     *
     * @return void
     */
    public function create(): void
    {
        $this->verifyRequest();

        $name = $this->getPostString('name');

        if (empty($name)) {
            $this->error(__('Preacher name is required.', 'sermon-browser'));
        }

        $id = Preacher::create([
            'name' => $name,
            'description' => '',
            'image' => '',
        ]);

        if ($id === 0) {
            $this->error(__('Failed to create preacher.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $name,
            'message' => __('Preacher created successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Update an existing preacher.
     *
     * Expected POST data:
     * - id: int (required) - The preacher ID.
     * - name: string (required) - The new preacher name.
     *
     * @return void
     */
    public function update(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');
        $name = $this->getPostString('name');

        if ($id <= 0) {
            $this->error(__('Invalid preacher ID.', 'sermon-browser'));
        }

        if (empty($name)) {
            $this->error(__('Preacher name is required.', 'sermon-browser'));
        }

        $result = Preacher::update($id, ['name' => $name]);

        if (!$result) {
            $this->error(__('Failed to update preacher.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $name,
            'message' => __('Preacher updated successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Delete a preacher.
     *
     * Expected POST data:
     * - id: int (required) - The preacher ID.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');

        if ($id <= 0) {
            $this->error(__('Invalid preacher ID.', 'sermon-browser'));
        }

        $result = Preacher::delete($id);

        if (!$result) {
            $this->error(__('Failed to delete preacher.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'message' => __('Preacher deleted successfully.', 'sermon-browser'),
        ]);
    }
}
