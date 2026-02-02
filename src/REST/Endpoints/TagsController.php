<?php

/**
 * Tags REST Controller.
 *
 * Handles all REST API endpoints for tags including listing
 * tags with sermon counts (tag cloud) and getting sermons by tag.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\Constants;
use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Facades\Sermon;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class TagsController
 *
 * REST API controller for tag endpoints.
 * These are read-only public endpoints.
 */
class TagsController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected $rest_base = 'tags';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /tags - List all tags with sermon counts (tag cloud data).
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

        // GET /tags/{name}/sermons - Get sermons with this tag.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<name>[^/]+)/sermons',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_sermons_by_tag'],
                    'permission_callback' => [$this, 'get_sermons_by_tag_permissions_check'],
                    'args' => [
                        'name' => [
                            'description' => __('The tag name.', 'sermon-browser'),
                            'type' => 'string',
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
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
            'limit' => [
                'description' => __('Maximum number of tags to return.', 'sermon-browser'),
                'type' => 'integer',
                'default' => 0,
                'minimum' => 0,
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    /**
     * Check if the current user can list tags.
     *
     * Tags are public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if the current user can get sermons by tag.
     *
     * This is public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_sermons_by_tag_permissions_check($_request): bool
    {
        return $this->get_items_permissions_check($_request);
    }

    /**
     * Get a list of tags with sermon counts (tag cloud data).
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        $limit = (int) ($request->get_param('limit') ?? 0);

        // Get tags with sermon counts.
        $tags = Tag::findAllWithSermonCount($limit);
        $total = count($tags);

        // Prepare response data.
        $data = array_map([$this, 'prepare_tag_for_response'], $tags);

        $response = new WP_REST_Response($data);

        return $this->prepare_pagination_response($response, $total, $total > 0 ? $total : 1);
    }

    /**
     * Get sermons that have a specific tag.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or error.
     */
    public function get_sermons_by_tag($request): WP_REST_Response|WP_Error
    {
        $name = $request->get_param('name');

        // Find the tag by name.
        $tag = Tag::findByName($name);

        if ($tag === null) {
            return $this->prepare_error_response(
                __(Constants::ERR_TAG_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        // Get sermon IDs for this tag.
        $sermonIds = Tag::getSermonIdsByTag((int) $tag->id);

        // If no sermons, return empty array.
        if (empty($sermonIds)) {
            $response = new WP_REST_Response([]);
            return $this->prepare_pagination_response($response, 0, 1);
        }

        // Get full sermon data for each ID.
        $sermons = [];
        foreach ($sermonIds as $sermonId) {
            $sermon = Sermon::findWithRelations($sermonId);
            if ($sermon !== null) {
                $sermons[] = $sermon;
            }
        }

        $total = count($sermons);

        // Prepare response data.
        $data = array_map([$this, 'prepare_sermon_for_response'], $sermons);

        $response = new WP_REST_Response($data);

        return $this->prepare_pagination_response($response, $total, $total > 0 ? $total : 1);
    }

    /**
     * Prepare a tag object for the response.
     *
     * @param object $tag The tag object.
     * @return array<string, mixed> The prepared data.
     */
    protected function prepare_tag_for_response(object $tag): array
    {
        $data = (array) $tag;

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
