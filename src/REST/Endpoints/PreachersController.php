<?php

/**
 * Preachers REST Controller.
 *
 * Handles all REST API endpoints for preachers including listing,
 * single preacher retrieval, creation, updates, and deletion.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\Constants;
use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\Preacher;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class PreachersController
 *
 * REST API controller for preacher endpoints.
 */
class PreachersController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected $rest_base = 'preachers';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /preachers - List all preachers with sermon counts.
        // POST /preachers - Create a new preacher.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_args(),
                ],
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args' => $this->get_create_args(),
                ],
            ]
        );

        // GET/PUT/DELETE /preachers/{id} - Single preacher operations.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the preacher.', 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
                [
                    'methods' => 'PUT,PATCH',
                    'callback' => [$this, 'update_item'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args' => $this->get_update_args(),
                ],
                [
                    'methods' => 'DELETE',
                    'callback' => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the preacher.', 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Get the arguments for the collection endpoint.
     *
     * @return array<string, array<string, mixed>> Collection arguments.
     */
    protected function get_collection_args(): array
    {
        $params = [];

        $params['search'] = [
            'description' => __('Search by preacher name.', 'sermon-browser'),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];

        return $params;
    }

    /**
     * Get the arguments for creating a preacher.
     *
     * @return array<string, array<string, mixed>> Create arguments.
     */
    protected function get_create_args(): array
    {
        return [
            'name' => [
                'description' => __('The preacher name.', 'sermon-browser'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'description' => [
                'description' => __('The preacher description or bio.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'image' => [
                'description' => __('The preacher image URL or path.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get the arguments for updating a preacher.
     *
     * @return array<string, array<string, mixed>> Update arguments.
     */
    protected function get_update_args(): array
    {
        $args = [
            'id' => [
                'description' => __('Unique identifier for the preacher.', 'sermon-browser'),
                'type' => 'integer',
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];

        // Add create args but make them optional.
        $createArgs = $this->get_create_args();
        foreach ($createArgs as $key => $arg) {
            $arg['required'] = false;
            $args[$key] = $arg;
        }

        return $args;
    }

    /**
     * Check if the current user can list preachers.
     *
     * Preachers are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can view a single preacher.
     *
     * Preachers are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_item_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can create a preacher.
     *
     * Requires the 'edit_posts' capability.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error True if allowed, WP_Error otherwise.
     */
    public function create_item_permissions_check($request): bool|WP_Error
    {
        if (!$this->check_edit_permission()) {
            return $this->prepare_error_response(
                __('You do not have permission to create preachers.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can update a preacher.
     *
     * Requires the 'edit_posts' capability.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error True if allowed, WP_Error otherwise.
     */
    public function update_item_permissions_check($request): bool|WP_Error
    {
        if (!$this->check_edit_permission()) {
            return $this->prepare_error_response(
                __('You do not have permission to update preachers.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can delete a preacher.
     *
     * Requires the 'edit_posts' capability.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error True if allowed, WP_Error otherwise.
     */
    public function delete_item_permissions_check($request): bool|WP_Error
    {
        if (!$this->check_edit_permission()) {
            return $this->prepare_error_response(
                __('You do not have permission to delete preachers.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Get a list of preachers.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        // Check for search query.
        $search = $request->get_param('search');
        if (!empty($search)) {
            return $this->get_search_results($search);
        }

        // Get all preachers with sermon counts.
        $preachers = Preacher::findAllWithSermonCount();
        $total = count($preachers);

        // Prepare response data.
        $data = array_map([$this, 'prepare_preacher_for_response'], $preachers);

        $response = new WP_REST_Response($data);

        return $this->prepare_pagination_response($response, $total, $total > 0 ? $total : 1);
    }

    /**
     * Get search results for preachers.
     *
     * @param string $search The search term.
     * @return WP_REST_Response The response.
     */
    protected function get_search_results(string $search): WP_REST_Response
    {
        $preachers = Preacher::searchByName($search);

        $data = array_map([$this, 'prepare_preacher_for_response'], $preachers);

        $response = new WP_REST_Response($data);
        $total = count($data);

        return $this->prepare_pagination_response($response, $total, $total > 0 ? $total : 1);
    }

    /**
     * Get a single preacher.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $preacher = Preacher::find($id);

        if ($preacher === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_PREACHER_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = $this->prepare_preacher_for_response($preacher);

        return new WP_REST_Response($data);
    }

    /**
     * Create a new preacher.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function create_item($request): WP_REST_Response|WP_Error
    {
        $name = $request->get_param('name');

        if (empty($name)) {
            return $this->prepare_error_response(
                __('Name is required.', 'sermon-browser'),
                400
            );
        }

        $data = [
            'name' => $name,
        ];

        // Optional fields.
        $optionalFields = ['description', 'image'];
        foreach ($optionalFields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        $id = Preacher::create($data);

        if (!$id) {
            return $this->prepare_error_response(
                __('Failed to create preacher.', 'sermon-browser'),
                500
            );
        }

        $preacher = Preacher::find($id);
        $responseData = $this->prepare_preacher_for_response($preacher);

        $response = new WP_REST_Response($responseData, 201);

        return $response;
    }

    /**
     * Update an existing preacher.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function update_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $preacher = Preacher::find($id);

        if ($preacher === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_PREACHER_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = [];

        // Collect updateable fields.
        $updateableFields = ['name', 'description', 'image'];
        foreach ($updateableFields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (!empty($data)) {
            Preacher::update($id, $data);
        }

        $updatedPreacher = Preacher::find($id);
        $responseData = $this->prepare_preacher_for_response($updatedPreacher);

        return new WP_REST_Response($responseData);
    }

    /**
     * Delete a preacher.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function delete_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $preacher = Preacher::find($id);

        if ($preacher === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_PREACHER_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $deleted = Preacher::delete($id);

        return new WP_REST_Response([
            'deleted' => $deleted,
            'previous' => $this->prepare_preacher_for_response($preacher),
        ]);
    }

    /**
     * Prepare a preacher object for the response.
     *
     * @param object $preacher The preacher object.
     * @return array<string, mixed> The prepared data.
     */
    protected function prepare_preacher_for_response(object $preacher): array
    {
        $data = (array) $preacher;

        // Ensure numeric IDs are integers.
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }

        // Ensure sermon_count is an integer if present.
        if (isset($data['sermon_count'])) {
            $data['sermon_count'] = (int) $data['sermon_count'];
        }

        return $this->prepare_item_response($data);
    }
}
