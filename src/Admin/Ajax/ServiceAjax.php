<?php
/**
 * Service AJAX Handler.
 *
 * Handles AJAX requests for service CRUD operations.
 * Services have a special time component that affects linked sermons.
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

use SermonBrowser\Facades\Service;

/**
 * Class ServiceAjax
 *
 * AJAX handler for service (church service time) operations.
 */
class ServiceAjax extends AjaxHandler
{
    /**
     * {@inheritDoc}
     */
    protected string $nonceAction = 'sb_service_nonce';

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        add_action('wp_ajax_sb_service_create', [$this, 'create']);
        add_action('wp_ajax_sb_service_update', [$this, 'update']);
        add_action('wp_ajax_sb_service_delete', [$this, 'delete']);
    }

    /**
     * Parse service input string.
     *
     * Service input comes in format "Service Name @ HH:MM".
     *
     * @param string $input The raw input.
     * @return array{name: string, time: string} Parsed name and time.
     */
    private function parseServiceInput(string $input): array
    {
        $parts = explode('@', $input);
        $name = trim($parts[0] ?? '');
        $time = trim($parts[1] ?? '00:00');

        // Validate time format
        if (!preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time = '00:00';
        }

        return [
            'name' => $name,
            'time' => $time,
        ];
    }

    /**
     * Create a new service.
     *
     * Expected POST data:
     * - name: string (required) - Format: "Service Name @ HH:MM".
     *
     * @return void
     */
    public function create(): void
    {
        $this->verifyRequest();

        $input = $this->getPostString('name');
        $parsed = $this->parseServiceInput($input);

        if (empty($parsed['name'])) {
            $this->error(__('Service name is required.', 'sermon-browser'));
        }

        $id = Service::create([
            'name' => $parsed['name'],
            'time' => $parsed['time'],
        ]);

        if ($id === 0) {
            $this->error(__('Failed to create service.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $parsed['name'],
            'time' => $parsed['time'],
            'message' => __('Service created successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Update an existing service.
     *
     * When the service time changes, all linked sermons (without override)
     * have their datetime adjusted by the time difference.
     *
     * Expected POST data:
     * - id: int (required) - The service ID.
     * - name: string (required) - Format: "Service Name @ HH:MM".
     *
     * @return void
     */
    public function update(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');
        $input = $this->getPostString('name');
        $parsed = $this->parseServiceInput($input);

        if ($id <= 0) {
            $this->error(__('Invalid service ID.', 'sermon-browser'));
        }

        if (empty($parsed['name'])) {
            $this->error(__('Service name is required.', 'sermon-browser'));
        }

        // Use the special update method that handles time cascade
        $result = Service::updateWithTimeShift($id, $parsed['name'], $parsed['time']);

        if (!$result) {
            $this->error(__('Failed to update service.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $parsed['name'],
            'time' => $parsed['time'],
            'message' => __('Service updated successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Delete a service.
     *
     * Expected POST data:
     * - id: int (required) - The service ID.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');

        if ($id <= 0) {
            $this->error(__('Invalid service ID.', 'sermon-browser'));
        }

        $result = Service::delete($id);

        if (!$result) {
            $this->error(__('Failed to delete service.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'message' => __('Service deleted successfully.', 'sermon-browser'),
        ]);
    }
}
