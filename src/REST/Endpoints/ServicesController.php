<?php

/**
 * Services REST Controller.
 *
 * Handles all REST API endpoints for services including listing,
 * single service retrieval, creation, updates, and deletion.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\Constants;
use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class ServicesController
 *
 * REST API controller for service endpoints.
 */
class ServicesController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected string $rest_base = 'services';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /services - List all services.
        // POST /services - Create a new service.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args' => $this->get_create_args(),
                ],
            ]
        );

        // GET/PUT/DELETE /services/{id} - Single service operations.
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
                            'description' => __('Unique identifier for the service.', 'sermon-browser'),
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
                            'description' => __('Unique identifier for the service.', 'sermon-browser'),
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
     * Get the arguments for creating a service.
     *
     * @return array<string, array<string, mixed>> Create arguments.
     */
    protected function get_create_args(): array
    {
        return [
            'name' => [
                'description' => __('The service name.', 'sermon-browser'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'time' => [
                'description' => __('The service time (HH:MM format).', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get the arguments for updating a service.
     *
     * @return array<string, array<string, mixed>> Update arguments.
     */
    protected function get_update_args(): array
    {
        $args = [
            'id' => [
                'description' => __('Unique identifier for the service.', 'sermon-browser'),
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
     * Check if the current user can list services.
     *
     * Services are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can view a single service.
     *
     * Services are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_item_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can create a service.
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
                __('You do not have permission to create services.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can update a service.
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
                __('You do not have permission to update services.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can delete a service.
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
                __('You do not have permission to delete services.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Get a list of services.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        $perPage = (int) ($request->get_param('per_page') ?? 10);

        // Get all services sorted by name.
        $services = Service::findAllSorted();
        $total = Service::count([]);

        // Prepare response data.
        $data = array_map([$this, 'prepare_service_for_response'], $services);

        $response = new WP_REST_Response($data);

        return $this->prepare_pagination_response($response, $total, $perPage);
    }

    /**
     * Get a single service.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $service = Service::find($id);

        if ($service === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERVICE_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = $this->prepare_service_for_response($service);

        return new WP_REST_Response($data);
    }

    /**
     * Create a new service.
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
        $time = $request->get_param('time');
        if ($time !== null) {
            $data['time'] = $time;
        }

        $id = Service::create($data);

        if (!$id) {
            return $this->prepare_error_response(
                __('Failed to create service.', 'sermon-browser'),
                500
            );
        }

        $service = Service::find($id);
        $responseData = $this->prepare_service_for_response($service);

        $response = new WP_REST_Response($responseData, 201);

        return $response;
    }

    /**
     * Update an existing service.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function update_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $service = Service::find($id);

        if ($service === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERVICE_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = [];

        // Collect updateable fields.
        $updateableFields = ['name', 'time'];
        foreach ($updateableFields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (!empty($data)) {
            Service::update($id, $data);
        }

        $updatedService = Service::find($id);
        $responseData = $this->prepare_service_for_response($updatedService);

        return new WP_REST_Response($responseData);
    }

    /**
     * Delete a service.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function delete_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $service = Service::find($id);

        if ($service === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERVICE_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $deleted = Service::delete($id);

        return new WP_REST_Response([
            'deleted' => $deleted,
            'previous' => $this->prepare_service_for_response($service),
        ]);
    }

    /**
     * Prepare a service object for the response.
     *
     * @param object $service The service object.
     * @return array<string, mixed> The prepared data.
     */
    protected function prepare_service_for_response(object $service): array
    {
        $data = (array) $service;

        // Ensure numeric IDs are integers.
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }

        return $this->prepare_item_response($data);
    }
}
