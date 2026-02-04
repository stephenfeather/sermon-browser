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

use SermonBrowser\Constants;
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
    protected $rest_base = 'sermons';

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

        // GET /sermons/render - Render sermon list HTML for dynamic filtering.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/render',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'render_sermon_list'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_render_args(),
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
                            'description' => __(Constants::DESC_SERMON_ID, 'sermon-browser'),
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
                            'description' => __(Constants::DESC_SERMON_ID, 'sermon-browser'),
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
     * Get the arguments for the render endpoint.
     *
     * @return array<string, array<string, mixed>> Render arguments.
     */
    protected function get_render_args(): array
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
            'preacher' => [
                'description' => __('Filter by preacher ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'series' => [
                'description' => __('Filter by series ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'service' => [
                'description' => __('Filter by service ID.', 'sermon-browser'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'book' => [
                'description' => __('Filter by Bible book name.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'stag' => [
                'description' => __('Filter by tag slug.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'date' => [
                'description' => __('Filter by start date (YYYY-MM-DD).', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'enddate' => [
                'description' => __('Filter by end date (YYYY-MM-DD).', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'title' => [
                'description' => __('Filter by title search.', 'sermon-browser'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'description' => __('Sort by field.', 'sermon-browser'),
                'type' => 'string',
                'default' => 'datetime',
                'enum' => ['datetime', 'title', 'preacher', 'series'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'description' => __('Sort direction.', 'sermon-browser'),
                'type' => 'string',
                'default' => 'desc',
                'enum' => ['asc', 'desc'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
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
                'description' => __(Constants::DESC_SERMON_ID, 'sermon-browser'),
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
     * Sermons are public but rate limited.
     *
     * @param WP_REST_Request $request The request object.
     * @return true|WP_Error True if allowed, WP_Error if rate limited.
     */
    public function get_items_permissions_check($request): true|WP_Error
    {
        return $this->check_rate_limit($request);
    }

    /**
     * Check if the current user can view a single sermon.
     *
     * Sermons are public but rate limited.
     *
     * @param WP_REST_Request $request The request object.
     * @return true|WP_Error True if allowed, WP_Error if rate limited.
     */
    public function get_item_permissions_check($request): true|WP_Error
    {
        return $this->check_rate_limit($request);
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
        $response = $this->prepare_pagination_response($response, $total, $perPage);

        return $this->add_rate_limit_headers($response, $request);
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
     * Render sermon list HTML for dynamic filtering.
     *
     * Returns pre-rendered HTML that can be used to update the DOM
     * without client-side templating.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The response with rendered HTML.
     */
    public function render_sermon_list($request): WP_REST_Response
    {
        $page = (int) ($request->get_param('page') ?? 1);
        $limit = (int) ($request->get_param('per_page') ?? 10);

        // Build filter from request params.
        $filter = [
            'preacher' => (int) ($request->get_param('preacher') ?? 0),
            'series' => (int) ($request->get_param('series') ?? 0),
            'service' => (int) ($request->get_param('service') ?? 0),
            'book' => $request->get_param('book') ?? '',
            'tag' => $request->get_param('stag') ?? '',
            'date' => $request->get_param('date') ?? '',
            'enddate' => $request->get_param('enddate') ?? '',
            'title' => $request->get_param('title') ?? '',
        ];

        // Map orderBy to database column.
        $orderBy = $request->get_param('orderby') ?? 'datetime';
        $orderByMap = [
            'datetime' => 'm.datetime',
            'title' => 'm.title',
            'preacher' => 'p.name',
            'series' => 'ss.name',
        ];
        $sortBy = $orderByMap[$orderBy] ?? 'm.datetime';

        $sortOrder = [
            'by' => $sortBy,
            'dir' => strtolower($request->get_param('order') ?? 'desc') === 'asc' ? 'asc' : 'desc',
        ];

        // Fetch sermons using the existing function.
        global $record_count;
        $sermons = sb_get_sermons($filter, $sortOrder, $page, $limit);

        // Start output buffering to capture rendered HTML.
        ob_start();

        if (empty($sermons)) {
            ?>
            <p class="sb-sermon-list__no-results">
                <?php esc_html_e('No sermons found.', 'sermon-browser'); ?>
            </p>
            <?php
        } else {
            ?>
            <div class="sb-sermon-list__results">
                <p class="sb-sermon-list__count">
                    <?php
                    printf(
                        /* translators: %d: number of sermons */
                        _n('%d sermon found', '%d sermons found', $record_count, 'sermon-browser'),
                        $record_count
                    );
                    ?>
                </p>

                <ul class="sb-sermon-list__items">
                    <?php foreach ($sermons as $sermon) : ?>
                        <li class="sb-sermon-list__item">
                            <article class="sb-sermon-list__sermon">
                                <h3 class="sb-sermon-list__sermon-title">
                                    <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id])); ?>">
                                        <?php echo esc_html($sermon->title); ?>
                                    </a>
                                </h3>

                                <div class="sb-sermon-list__sermon-meta">
                                    <?php if (!empty($sermon->datetime)) : ?>
                                        <span class="sb-sermon-list__sermon-date">
                                            <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($sermon->preacher)) : ?>
                                        <span class="sb-sermon-list__sermon-preacher">
                                            <?php echo esc_html($sermon->preacher); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($sermon->series)) : ?>
                                        <span class="sb-sermon-list__sermon-series">
                                            <?php echo esc_html($sermon->series); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($sermon->description)) : ?>
                                    <div class="sb-sermon-list__sermon-excerpt">
                                        <?php echo esc_html(wp_trim_words($sermon->description, 30)); ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }

        $resultsHtml = ob_get_clean();

        // Generate pagination HTML.
        ob_start();

        if ($record_count > $limit) {
            $totalPages = (int) ceil($record_count / $limit);
            ?>
            <nav class="sb-sermon-list__pagination" aria-label="<?php esc_attr_e('Sermon pagination', 'sermon-browser'); ?>">
                <?php if ($page > 1) : ?>
                    <button type="button" class="sb-sermon-list__pagination-prev" data-page="<?php echo esc_attr($page - 1); ?>">
                        &laquo; <?php esc_html_e('Previous', 'sermon-browser'); ?>
                    </button>
                <?php endif; ?>

                <span class="sb-sermon-list__pagination-info">
                    <?php
                    printf(
                        /* translators: 1: current page, 2: total pages */
                        esc_html__('Page %1$d of %2$d', 'sermon-browser'),
                        $page,
                        $totalPages
                    );
                    ?>
                </span>

                <?php if ($page < $totalPages) : ?>
                    <button type="button" class="sb-sermon-list__pagination-next" data-page="<?php echo esc_attr($page + 1); ?>">
                        <?php esc_html_e('Next', 'sermon-browser'); ?> &raquo;
                    </button>
                <?php endif; ?>
            </nav>
            <?php
        }

        $paginationHtml = ob_get_clean();

        $response = new WP_REST_Response([
            'html' => $resultsHtml,
            'pagination' => $paginationHtml,
            'total' => $record_count,
            'totalPages' => $limit > 0 ? (int) ceil($record_count / $limit) : 0,
            'page' => $page,
        ]);

        return $this->add_rate_limit_headers($response, $request);
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
                __(Constants::ERR_SERMON_NOT_FOUND, 'sermon-browser'),
                404
            );
        }

        $data = $this->prepare_sermon_for_response($sermon);

        $response = new WP_REST_Response($data);

        return $this->add_rate_limit_headers($response, $request);
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

        return new WP_REST_Response($responseData, 201);
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
                __(Constants::ERR_SERMON_NOT_FOUND, 'sermon-browser'),
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
                __(Constants::ERR_SERMON_NOT_FOUND, 'sermon-browser'),
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
