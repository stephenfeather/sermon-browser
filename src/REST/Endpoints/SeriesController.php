<?php

/**
 * Series REST Controller.
 *
 * Handles all REST API endpoints for sermon series including listing,
 * single series retrieval, creation, updates, and deletion.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\Constants;
use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Sermon;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class SeriesController
 *
 * REST API controller for series endpoints.
 */
class SeriesController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected $rest_base = 'series';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /series - List all series.
        // POST /series - Create a new series.
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

        // GET/PUT/DELETE /series/{id} - Single series operations.
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
                            'description' => __(Constants::DESC_SERIES_ID, 'sermon-browser'),
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
                            'description' => __(Constants::DESC_SERIES_ID, 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );

        // GET /series/{id}/sermons - Get sermons in this series.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/sermons',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_sermons'],
                    'permission_callback' => [$this, 'get_sermons_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __(Constants::DESC_SERIES_ID, 'sermon-browser'),
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
        return [
            'search' => [
                'description' => __('Search by series name.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get the arguments for creating a series.
     *
     * @return array<string, array<string, mixed>> Create arguments.
     */
    protected function get_create_args(): array
    {
        return [
            'name' => [
                'description' => __('The series name.', 'sermon-browser'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'page_id' => [
                'description' => __('The linked page ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    /**
     * Get the arguments for updating a series.
     *
     * @return array<string, array<string, mixed>> Update arguments.
     */
    protected function get_update_args(): array
    {
        $args = [
            'id' => [
                'description' => __(Constants::DESC_SERIES_ID, 'sermon-browser'),
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
     * Check if the current user can list series.
     *
     * Series are public but rate limited.
     *
     * @param WP_REST_Request $request The request object.
     * @return true|WP_Error True if allowed, WP_Error if rate limited.
     */
    public function get_items_permissions_check($request): true|WP_Error
    {
        return $this->check_rate_limit($request);
    }

    /**
     * Check if the current user can view a single series.
     *
     * Series are public but rate limited.
     *
     * @param WP_REST_Request $request The request object.
     * @return true|WP_Error True if allowed, WP_Error if rate limited.
     */
    public function get_item_permissions_check($request): true|WP_Error
    {
        return $this->check_rate_limit($request);
    }

    /**
     * Check if the current user can view sermons in a series.
     *
     * Sermons are public but rate limited.
     *
     * @param WP_REST_Request $request The request object.
     * @return true|WP_Error True if allowed, WP_Error if rate limited.
     */
    public function get_sermons_permissions_check($request): true|WP_Error
    {
        return $this->check_rate_limit($request);
    }

    /**
     * Check if the current user can create a series.
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
                __('You do not have permission to create series.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can update a series.
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
                __('You do not have permission to update series.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can delete a series.
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
                __('You do not have permission to delete series.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Get a list of series.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        // Check for search query.
        $search = $request->get_param('search');
        if (!empty($search)) {
            $seriesList = Series::searchByName($search);
        } else {
            $seriesList = Series::findAllSorted();
        }

        // Prepare response data.
        $data = array_map([$this, 'prepare_series_for_response'], $seriesList);

        $response = new WP_REST_Response($data);

        return $this->add_rate_limit_headers($response, $request);
    }

    /**
     * Get a single series.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $series = Series::find($id);

        if ($series === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERIES_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = $this->prepare_series_for_response($series);

        $response = new WP_REST_Response($data);

        return $this->add_rate_limit_headers($response, $request);
    }

    /**
     * Get sermons in a series.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_sermons($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $series = Series::find($id);

        if ($series === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERIES_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $sermons = Sermon::findBySeries($id, 0);

        // Prepare response data.
        $data = array_map([$this, 'prepare_sermon_for_response'], $sermons);

        $response = new WP_REST_Response($data);

        return $this->add_rate_limit_headers($response, $request);
    }

    /**
     * Create a new series.
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
        $pageId = $request->get_param('page_id');
        if ($pageId !== null) {
            $data['page_id'] = $pageId;
        }

        $id = Series::create($data);

        if (!$id) {
            return $this->prepare_error_response(
                __('Failed to create series.', 'sermon-browser'),
                500
            );
        }

        $series = Series::find($id);
        $responseData = $this->prepare_series_for_response($series);

        return new WP_REST_Response($responseData, 201);
    }

    /**
     * Update an existing series.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function update_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $series = Series::find($id);

        if ($series === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERIES_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = [];

        // Collect updateable fields.
        $updateableFields = ['name', 'page_id'];
        foreach ($updateableFields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (!empty($data)) {
            Series::update($id, $data);
        }

        $updatedSeries = Series::find($id);
        $responseData = $this->prepare_series_for_response($updatedSeries);

        return new WP_REST_Response($responseData);
    }

    /**
     * Delete a series.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function delete_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $series = Series::find($id);

        if ($series === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERIES_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $deleted = Series::delete($id);

        return new WP_REST_Response([
            'deleted' => $deleted,
            'previous' => $this->prepare_series_for_response($series),
        ]);
    }

    /**
     * Prepare a series object for the response.
     *
     * @param object $series The series object.
     * @return array<string, mixed> The prepared data.
     */
    protected function prepare_series_for_response(object $series): array
    {
        $data = (array) $series;

        // Ensure numeric IDs are integers.
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }

        if (isset($data['page_id'])) {
            $data['page_id'] = (int) $data['page_id'];
        }

        if (isset($data['sermon_count'])) {
            $data['sermon_count'] = (int) $data['sermon_count'];
        }

        return $this->prepare_item_response($data);
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
