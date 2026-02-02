<?php

/**
 * Files REST Controller.
 *
 * Handles all REST API endpoints for files including listing,
 * single file retrieval, sermon files, and file management.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\Constants;
use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Sermon;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class FilesController
 *
 * REST API controller for file endpoints.
 */
class FilesController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected $rest_base = 'files';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /files - List all files.
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
            ]
        );

        // GET/DELETE /files/{id} - Single file operations.
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
                            'description' => __('Unique identifier for the file.', 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
                [
                    'methods' => 'DELETE',
                    'callback' => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the file.', 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );

        // GET/POST /sermons/{id}/files - Files for a sermon.
        register_rest_route(
            $this->namespace,
            '/sermons/(?P<sermon_id>[\d]+)/' . $this->rest_base,
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_sermon_files'],
                    'permission_callback' => [$this, 'get_sermon_files_permissions_check'],
                    'args' => [
                        'sermon_id' => [
                            'description' => __('Unique identifier for the sermon.', 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'attach_file_to_sermon'],
                    'permission_callback' => [$this, 'attach_file_permissions_check'],
                    'args' => [
                        'sermon_id' => [
                            'description' => __('Unique identifier for the sermon.', 'sermon-browser'),
                            'type' => 'integer',
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ],
                        'file_id' => [
                            'description' => __('Unique identifier for the file to attach.', 'sermon-browser'),
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

        $params['type'] = [
            'description' => __('Filter by file type.', 'sermon-browser'),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];

        return $params;
    }

    /**
     * Check if the current user can list files.
     *
     * Files are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can view a single file.
     *
     * Files are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_item_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can view sermon files.
     *
     * Sermon files are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_sermon_files_permissions_check($_request): bool
    {
        return true;
    }

    /**
     * Check if the current user can attach files to sermons.
     *
     * Requires the 'edit_posts' capability.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error True if allowed, WP_Error otherwise.
     */
    public function attach_file_permissions_check($_request): bool|WP_Error
    {
        if (!$this->check_edit_permission()) {
            return $this->prepare_error_response(
                __('You do not have permission to attach files.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Check if the current user can delete a file.
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
                __('You do not have permission to delete files.', 'sermon-browser'),
                403
            );
        }

        return true;
    }

    /**
     * Get a list of files.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        $page = (int) ($request->get_param('page') ?? 1);
        $perPage = (int) ($request->get_param('per_page') ?? 10);
        $offset = ($page - 1) * $perPage;

        // Check for type filter.
        $type = $request->get_param('type');
        if (!empty($type)) {
            $files = File::findByType($type);
            $total = count($files);
        } else {
            $files = File::findAll([], $perPage, $offset);
            $total = File::count();
        }

        // Prepare response data.
        $data = array_map([$this, 'prepare_file_for_response'], $files);

        $response = new WP_REST_Response($data);

        return $this->prepare_pagination_response($response, $total, $perPage);
    }

    /**
     * Get a single file.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $file = File::find($id);

        if ($file === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_FILE_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = $this->prepare_file_for_response($file);

        return new WP_REST_Response($data);
    }

    /**
     * Get files for a sermon.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_sermon_files($request): WP_REST_Response|WP_Error
    {
        $sermonId = (int) $request->get_param('sermon_id');

        // Check if sermon exists.
        $sermon = Sermon::find($sermonId);

        if ($sermon === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERMON_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $files = File::findBySermon($sermonId);

        // Prepare response data.
        $data = array_map([$this, 'prepare_file_for_response'], $files);

        $response = new WP_REST_Response($data);
        $total = count($data);

        return $this->prepare_pagination_response($response, $total, $total);
    }

    /**
     * Attach a file to a sermon.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function attach_file_to_sermon($request): WP_REST_Response|WP_Error
    {
        $sermonId = (int) $request->get_param('sermon_id');
        $fileId = $request->get_param('file_id');

        // Check if sermon exists.
        $sermon = Sermon::find($sermonId);

        if ($sermon === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_SERMON_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        // Validate file_id is provided.
        if (empty($fileId)) {
            return $this->prepare_error_response(
                __('file_id is required.', 'sermon-browser'),
                400
            );
        }

        $fileId = (int) $fileId;

        // Check if file exists.
        $file = File::find($fileId);

        if ($file === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_FILE_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        // Link the file to the sermon.
        File::linkToSermon($fileId, $sermonId);

        // Return the updated file.
        $updatedFile = File::find($fileId);
        $data = $this->prepare_file_for_response($updatedFile);

        return new WP_REST_Response($data);
    }

    /**
     * Delete a file.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function delete_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        $file = File::find($id);

        if ($file === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_FILE_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $deleted = File::delete($id);

        return new WP_REST_Response([
            'deleted' => $deleted,
            'previous' => $this->prepare_file_for_response($file),
        ]);
    }

    /**
     * Prepare a file object for the response.
     *
     * @param object $file The file object.
     * @return array<string, mixed> The prepared data.
     */
    protected function prepare_file_for_response(object $file): array
    {
        $data = (array) $file;

        // Ensure numeric IDs are integers.
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }

        if (isset($data['sermon_id'])) {
            $data['sermon_id'] = (int) $data['sermon_id'];
        }

        if (isset($data['count'])) {
            $data['count'] = (int) $data['count'];
        }

        if (isset($data['duration'])) {
            $data['duration'] = (int) $data['duration'];
        }

        return $this->prepare_item_response($data);
    }
}
