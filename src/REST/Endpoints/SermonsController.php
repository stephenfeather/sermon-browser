<?php

/**
 * Sermons REST Controller.
 *
 * Handles all REST API endpoints for sermons including listing,
 * single sermon retrieval, creation, updates, and deletion.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\Sermon;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class SermonsController
 *
 * REST API controller for sermon endpoints.
 */
class SermonsController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected string $rest_base = 'sermons';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /sermons - List all sermons with pagination and filters.
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

        // GET/PUT/DELETE /sermons/{id} - Single sermon operations.
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
                            'description' => __('Unique identifier for the sermon.', 'sermon-browser'),
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
                            'description' => __('Unique identifier for the sermon.', 'sermon-browser'),
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
        $params = $this->get_collection_params();

        $params['preacher'] = [
            'description' => __('Filter by preacher ID.', 'sermon-browser'),
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ];

        $params['series'] = [
            'description' => __('Filter by series ID.', 'sermon-browser'),
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ];

        $params['service'] = [
            'description' => __('Filter by service ID.', 'sermon-browser'),
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ];

        $params['search'] = [
            'description' => __('Search by sermon title.', 'sermon-browser'),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];

        return $params;
    }

    /**
     * Get the arguments for creating a sermon.
     *
     * @return array<string, array<string, mixed>> Create arguments.
     */
    protected function get_create_args(): array
    {
        return [
            'title' => [
                'description' => __('The sermon title.', 'sermon-browser'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'preacher_id' => [
                'description' => __('The preacher ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'series_id' => [
                'description' => __('The series ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'service_id' => [
                'description' => __('The service ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'description' => [
                'description' => __('The sermon description.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'datetime' => [
                'description' => __('The sermon date and time.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get the arguments for updating a sermon.
     *
     * @return array<string, array<string, mixed>> Update arguments.
     */
    protected function get_update_args(): array
    {
        $args = [
            'id' => [
                'description' => __('Unique identifier for the sermon.', 'sermon-browser'),
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
     * Check if the current user can list sermons.
     *
     * Sermons are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can view a single sermon.
     *
     * Sermons are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_item_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can create a sermon.
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
                __('You do not have permission to create sermons.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can update a sermon.
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
                __('You do not have permission to update sermons.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can delete a sermon.
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
                __('You do not have permission to delete sermons.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Get a list of sermons.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        $page = (int) ($request->get_param('page') ?? 1);
        $perPage = (int) ($request->get_param('per_page') ?? 10);
        $offset = ($page - 1) * $perPage;

        // Check for search query.
        $search = $request->get_param('search');
        if (!empty($search)) {
            return $this->get_search_results($search, $perPage);
        }

        // Build filter criteria.
        $filter = [];

        $preacher = $request->get_param('preacher');
        if (!empty($preacher)) {
            $filter['preacher_id'] = (int) $preacher;
        }

        $series = $request->get_param('series');
        if (!empty($series)) {
            $filter['series_id'] = (int) $series;
        }

        $service = $request->get_param('service');
        if (!empty($service)) {
            $filter['service_id'] = (int) $service;
        }

        // Get sermons with relations.
        $sermons = Sermon::findAllWithRelations($filter, $perPage, $offset);
        $total = Sermon::countFiltered($filter);

        // Prepare response data.
        $data = array_map([$this, 'prepare_sermon_for_response'], $sermons);

        $response = new WP_REST_Response($data);

        return $this->prepare_pagination_response($response, $total, $perPage);
    }

    /**
     * Get search results for sermons.
     *
     * @param string $search The search term.
     * @param int $limit Maximum results.
     * @return WP_REST_Response The response.
     */
    protected function get_search_results(string $search, int $limit): WP_REST_Response
    {
        $sermons = Sermon::searchByTitle($search, $limit);

        $data = array_map([$this, 'prepare_sermon_for_response'], $sermons);

        $response = new WP_REST_Response($data);
        $total = count($data);

        return $this->prepare_pagination_response($response, $total, $limit);
    }

    /**
     * Get a single sermon.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $sermon = Sermon::findWithRelations($id);

        if ($sermon === null) {
            return $this->prepare_error_response(
                __('Sermon not found.', 'sermon-browser'),
                404
            );
        }

        $data = $this->prepare_sermon_for_response($sermon);

        return new WP_REST_Response($data);
    }

    /**
     * Create a new sermon.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function create_item($request): WP_REST_Response|WP_Error
    {
        $title = $request->get_param('title');

        if (empty($title)) {
            return $this->prepare_error_response(
                __('Title is required.', 'sermon-browser'),
                400
            );
        }

        $data = [
            'title' => $title,
        ];

        // Optional fields.
        $optionalFields = ['preacher_id', 'series_id', 'service_id', 'description', 'datetime'];
        foreach ($optionalFields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        $id = Sermon::create($data);

        if (!$id) {
            return $this->prepare_error_response(
                __('Failed to create sermon.', 'sermon-browser'),
                500
            );
        }

        $sermon = Sermon::findWithRelations($id);
        $responseData = $this->prepare_sermon_for_response($sermon);

        $response = new WP_REST_Response($responseData, 201);

        return $response;
    }

    /**
     * Update an existing sermon.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function update_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $sermon = Sermon::find($id);

        if ($sermon === null) {
            return $this->prepare_error_response(
                __('Sermon not found.', 'sermon-browser'),
                404
            );
        }

        $data = [];

        // Collect updateable fields.
        $updateableFields = ['title', 'preacher_id', 'series_id', 'service_id', 'description', 'datetime'];
        foreach ($updateableFields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (!empty($data)) {
            Sermon::update($id, $data);
        }

        $updatedSermon = Sermon::findWithRelations($id);
        $responseData = $this->prepare_sermon_for_response($updatedSermon);

        return new WP_REST_Response($responseData);
    }

    /**
     * Delete a sermon.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function delete_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $sermon = Sermon::find($id);

        if ($sermon === null) {
            return $this->prepare_error_response(
                __('Sermon not found.', 'sermon-browser'),
                404
            );
        }

        $deleted = Sermon::delete($id);

        return new WP_REST_Response([
            'deleted' => $deleted,
            'previous' => $this->prepare_sermon_for_response($sermon),
        ]);
    }

    /**
     * Prepare a sermon object for the response.
     *
     * @param object $sermon The sermon object.
     * @return array<string, mixed> The prepared data.
     */
    protected function prepare_sermon_for_response(object $sermon): array
    {
        $data = (array) $sermon;

        // Ensure numeric IDs are integers.
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }

        if (isset($data['preacher_id'])) {
            $data['preacher_id'] = (int) $data['preacher_id'];
        }

        if (isset($data['series_id'])) {
            $data['series_id'] = (int) $data['series_id'];
        }

        if (isset($data['service_id'])) {
            $data['service_id'] = (int) $data['service_id'];
        }

        return $this->prepare_item_response($data);
    }
}
