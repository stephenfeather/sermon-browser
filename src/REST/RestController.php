<?php
/**
 * REST Controller base class.
 *
 * Provides common functionality for all REST API endpoints including
 * authentication helpers, pagination, and response formatting.
 *
 * @package SermonBrowser\REST
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;

/**
 * Class RestController
 *
 * Abstract base class for REST API controllers.
 * Extends WP_REST_Controller and provides common utilities.
 */
abstract class RestController extends WP_REST_Controller
{
    /**
     * The REST namespace.
     *
     * @var string
     */
    protected string $namespace = 'sermon-browser/v1';

    /**
     * Check if the current user has admin permissions.
     *
     * Requires the 'manage_options' capability.
     *
     * @return bool True if user has admin permissions.
     */
    protected function check_admin_permission(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Check if the current user has edit permissions.
     *
     * Requires the 'edit_posts' capability.
     *
     * @return bool True if user has edit permissions.
     */
    protected function check_edit_permission(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Get standard collection parameters for pagination.
     *
     * Returns parameters for 'page' and 'per_page' with sensible defaults.
     *
     * @return array<string, array<string, mixed>> Collection parameters.
     */
    protected function get_collection_params(): array
    {
        return [
            'page' => [
                'description' => __('Current page of the collection.', 'sermon-browser'),
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'description' => __('Maximum number of items to return per page.', 'sermon-browser'),
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    /**
     * Prepare a response with pagination headers.
     *
     * Adds X-WP-Total and X-WP-TotalPages headers to the response.
     *
     * @param WP_REST_Response $response The response object.
     * @param int $total Total number of items.
     * @param int $perPage Items per page.
     * @return WP_REST_Response The response with pagination headers.
     */
    protected function prepare_pagination_response(
        WP_REST_Response $response,
        int $total,
        int $perPage
    ): WP_REST_Response {
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 0;

        $response->header('X-WP-Total', (string) $total);
        $response->header('X-WP-TotalPages', (string) $totalPages);

        return $response;
    }

    /**
     * Prepare an item for the response.
     *
     * Currently returns the item as-is, but provides a hook point
     * for child classes to add links, meta, or transform data.
     *
     * @param array<string, mixed> $item The item data.
     * @return array<string, mixed> The prepared item.
     */
    protected function prepare_item_response(array $item): array
    {
        return $item;
    }

    /**
     * Prepare an error response.
     *
     * Creates a standardized WP_Error object with the given message and status code.
     *
     * @param string $message The error message.
     * @param int $code The HTTP status code.
     * @return WP_Error The error object.
     */
    protected function prepare_error_response(string $message, int $code): WP_Error
    {
        return new WP_Error(
            'rest_error',
            $message,
            ['status' => $code]
        );
    }
}
