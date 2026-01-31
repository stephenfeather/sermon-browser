<?php

/**
 * Search REST Controller.
 *
 * Handles combined sermon search with text query and filters.
 *
 * @package SermonBrowser\REST\Endpoints
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST\Endpoints;

use SermonBrowser\REST\RestController;
use SermonBrowser\Facades\Sermon;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class SearchController
 *
 * REST API controller for combined sermon search.
 */
class SearchController extends RestController
{
    /**
     * The REST base for this controller.
     *
     * @var string
     */
    protected string $rest_base = 'search';

    /**
     * Register the routes for this controller.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // GET /search - Combined sermon search with filters.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_search_args(),
                ],
            ]
        );
    }

    /**
     * Get the arguments for the search endpoint.
     *
     * @return array<string, array<string, mixed>> Search arguments.
     */
    protected function get_search_args(): array
    {
        $params = $this->get_collection_params();

        $params['q'] = [
            'description' => __('Search term for sermon titles.', 'sermon-browser'),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];

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

        return $params;
    }

    /**
     * Check if the current user can perform a search.
     *
     * Search is public, so this always returns true.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool Always true for public access.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Perform a combined search with optional filters.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response.
     */
    public function get_items($request): WP_REST_Response
    {
        $page = (int) ($request->get_param('page') ?? 1);
        $perPage = (int) ($request->get_param('per_page') ?? 10);
        $offset = ($page - 1) * $perPage;

        // Build filter criteria.
        $filter = [];

        // Text search (q parameter).
        $query = $request->get_param('q');
        if (!empty($query)) {
            $filter['title'] = $query;
        }

        // Preacher filter.
        $preacher = $request->get_param('preacher');
        if (!empty($preacher)) {
            $filter['preacher_id'] = (int) $preacher;
        }

        // Series filter.
        $series = $request->get_param('series');
        if (!empty($series)) {
            $filter['series_id'] = (int) $series;
        }

        // Service filter.
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
