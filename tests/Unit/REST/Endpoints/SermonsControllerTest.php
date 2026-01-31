<?php

/**
 * Tests for SermonsController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\SermonsController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\SermonRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test SermonsController functionality.
 *
 * Tests the REST API endpoints for sermons.
 */
class SermonsControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var SermonsController
     */
    private SermonsController $controller;

    /**
     * Mock repository.
     *
     * @var \Mockery\MockInterface&SermonRepository
     */
    private $mockRepository;

    /**
     * Container instance.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset container singleton.
        Container::reset();

        // Set up mock repository.
        $this->mockRepository = Mockery::mock(SermonRepository::class);

        // Get container and inject mock.
        $this->container = Container::getInstance();
        $this->container->set(SermonRepository::class, $this->mockRepository);

        $this->controller = new SermonsController();
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        Container::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Route Registration Tests
    // =========================================================================

    /**
     * Test register_routes registers GET /sermons route.
     */
    public function testRegisterRoutesRegistersGetSermonsRoute(): void
    {
        $registeredRoutes = [];

        Functions\expect('register_rest_route')
            ->atLeast()
            ->times(1)
            ->andReturnUsing(function ($namespace, $route, $args) use (&$registeredRoutes) {
                $registeredRoutes[] = ['namespace' => $namespace, 'route' => $route, 'args' => $args];
                return true;
            });

        $this->controller->register_routes();

        // Check that /sermons route was registered.
        $sermonsRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/sermons');
        $this->assertNotEmpty($sermonsRoute, 'GET /sermons route should be registered');
    }

    /**
     * Test register_routes registers GET /sermons/{id} route.
     */
    public function testRegisterRoutesRegistersGetSingleSermonRoute(): void
    {
        $registeredRoutes = [];

        Functions\expect('register_rest_route')
            ->atLeast()
            ->times(1)
            ->andReturnUsing(function ($namespace, $route, $args) use (&$registeredRoutes) {
                $registeredRoutes[] = ['namespace' => $namespace, 'route' => $route, 'args' => $args];
                return true;
            });

        $this->controller->register_routes();

        // Check that /sermons/(?P<id>\d+) route was registered.
        $singleRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/sermons.*id/', $r['route'])
        );
        $this->assertNotEmpty($singleRoute, 'GET /sermons/{id} route should be registered');
    }

    /**
     * Test routes use correct namespace.
     */
    public function testRoutesUseCorrectNamespace(): void
    {
        $registeredRoutes = [];

        Functions\expect('register_rest_route')
            ->atLeast()
            ->times(1)
            ->andReturnUsing(function ($namespace, $route, $args) use (&$registeredRoutes) {
                $registeredRoutes[] = ['namespace' => $namespace, 'route' => $route, 'args' => $args];
                return true;
            });

        $this->controller->register_routes();

        foreach ($registeredRoutes as $route) {
            $this->assertEquals('sermon-browser/v1', $route['namespace']);
        }
    }

    // =========================================================================
    // GET /sermons Tests
    // =========================================================================

    /**
     * Test get_items returns list of sermons.
     */
    public function testGetItemsReturnsListOfSermons(): void
    {
        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'Sermon One',
                'preacher_name' => 'John Doe',
                'series_name' => 'Romans',
                'service_name' => 'Sunday AM',
            ],
            (object) [
                'id' => 2,
                'title' => 'Sermon Two',
                'preacher_name' => 'Jane Doe',
                'series_name' => 'Genesis',
                'service_name' => 'Sunday PM',
            ],
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with([], 10, 0)
            ->andReturn($sermons);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with([])
            ->andReturn(2);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('Sermon One', $data[0]['title']);
    }

    /**
     * Test get_items applies pagination.
     */
    public function testGetItemsAppliesPagination(): void
    {
        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with([], 5, 10)
            ->andReturn([]);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with([])
            ->andReturn(15);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');
        $request->set_param('page', 3);
        $request->set_param('per_page', 5);

        $response = $this->controller->get_items($request);

        $headers = $response->get_headers();
        $this->assertEquals(15, $headers['X-WP-Total']);
        $this->assertEquals(3, $headers['X-WP-TotalPages']);
    }

    /**
     * Test get_items filters by preacher ID.
     */
    public function testGetItemsFiltersByPreacher(): void
    {
        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['preacher_id' => 5], 10, 0)
            ->andReturn([]);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with(['preacher_id' => 5])
            ->andReturn(0);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);
        $request->set_param('preacher', 5);

        $this->controller->get_items($request);
    }

    /**
     * Test get_items filters by series ID.
     */
    public function testGetItemsFiltersBySeries(): void
    {
        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['series_id' => 3], 10, 0)
            ->andReturn([]);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with(['series_id' => 3])
            ->andReturn(0);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);
        $request->set_param('series', 3);

        $this->controller->get_items($request);
    }

    /**
     * Test get_items filters by service ID.
     */
    public function testGetItemsFiltersByService(): void
    {
        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['service_id' => 2], 10, 0)
            ->andReturn([]);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with(['service_id' => 2])
            ->andReturn(0);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);
        $request->set_param('service', 2);

        $this->controller->get_items($request);
    }

    /**
     * Test get_items searches by title.
     */
    public function testGetItemsSearchesByTitle(): void
    {
        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'The Gospel of Grace',
                'preacher_name' => 'John Doe',
            ],
        ];

        $this->mockRepository
            ->shouldReceive('searchByTitle')
            ->once()
            ->with('Gospel', 10)
            ->andReturn($sermons);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);
        $request->set_param('search', 'Gospel');

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
    }

    // =========================================================================
    // GET /sermons/{id} Tests
    // =========================================================================

    /**
     * Test get_item returns single sermon.
     */
    public function testGetItemReturnsSingleSermon(): void
    {
        $sermon = (object) [
            'id' => 1,
            'title' => 'Test Sermon',
            'preacher_name' => 'John Doe',
            'preacher_description' => 'Pastor',
            'preacher_image' => 'image.jpg',
            'series_name' => 'Romans',
            'service_name' => 'Sunday AM',
            'service_time' => '10:00',
        ];

        $this->mockRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(1)
            ->andReturn($sermon);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons/1');
        $request->set_param('id', 1);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Test Sermon', $data['title']);
        $this->assertEquals('John Doe', $data['preacher_name']);
    }

    /**
     * Test get_item returns 404 when sermon not found.
     */
    public function testGetItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons/999');
        $request->set_param('id', 999);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Sermon not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // POST /sermons Tests
    // =========================================================================

    /**
     * Test create_item creates new sermon.
     */
    public function testCreateItemCreatesNewSermon(): void
    {
        $newSermon = (object) [
            'id' => 10,
            'title' => 'New Sermon',
            'preacher_id' => 1,
            'series_id' => 2,
            'service_id' => 3,
        ];

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'title' => 'New Sermon',
                'preacher_id' => 1,
                'series_id' => 2,
                'service_id' => 3,
                'description' => 'A test sermon',
            ])
            ->andReturn(10);

        $this->mockRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(10)
            ->andReturn($newSermon);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons');
        $request->set_param('title', 'New Sermon');
        $request->set_param('preacher_id', 1);
        $request->set_param('series_id', 2);
        $request->set_param('service_id', 3);
        $request->set_param('description', 'A test sermon');

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(201, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals(10, $data['id']);
    }

    /**
     * Test create_item requires title.
     */
    public function testCreateItemRequiresTitle(): void
    {
        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons');
        // No title set.

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    /**
     * Test create_item permission check requires edit_posts.
     */
    public function testCreateItemPermissionCheckRequiresEditPosts(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons');

        $result = $this->controller->create_item_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * Test create_item permission check returns true when user has edit_posts.
     */
    public function testCreateItemPermissionCheckReturnsTrueWithCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons');

        $result = $this->controller->create_item_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // PUT /sermons/{id} Tests
    // =========================================================================

    /**
     * Test update_item updates existing sermon.
     */
    public function testUpdateItemUpdatesExistingSermon(): void
    {
        $existingSermon = (object) [
            'id' => 5,
            'title' => 'Original Title',
        ];

        $updatedSermon = (object) [
            'id' => 5,
            'title' => 'Updated Title',
            'preacher_name' => 'John Doe',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingSermon);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['title' => 'Updated Title'])
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(5)
            ->andReturn($updatedSermon);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/sermons/5');
        $request->set_param('id', 5);
        $request->set_param('title', 'Updated Title');

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Updated Title', $data['title']);
    }

    /**
     * Test update_item returns 404 when sermon not found.
     */
    public function testUpdateItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/sermons/999');
        $request->set_param('id', 999);
        $request->set_param('title', 'Updated Title');

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    /**
     * Test update_item permission check requires edit_posts.
     */
    public function testUpdateItemPermissionCheckRequiresEditPosts(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/sermons/5');
        $request->set_param('id', 5);

        $result = $this->controller->update_item_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    // =========================================================================
    // DELETE /sermons/{id} Tests
    // =========================================================================

    /**
     * Test delete_item deletes sermon.
     */
    public function testDeleteItemDeletesSermon(): void
    {
        $existingSermon = (object) [
            'id' => 5,
            'title' => 'To Be Deleted',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingSermon);

        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(true);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/sermons/5');
        $request->set_param('id', 5);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertTrue($data['deleted']);
    }

    /**
     * Test delete_item returns 404 when sermon not found.
     */
    public function testDeleteItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/sermons/999');
        $request->set_param('id', 999);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    /**
     * Test delete_item permission check requires edit_posts.
     */
    public function testDeleteItemPermissionCheckRequiresEditPosts(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/sermons/5');
        $request->set_param('id', 5);

        $result = $this->controller->delete_item_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    // =========================================================================
    // Public Access Tests
    // =========================================================================

    /**
     * Test get_items_permissions_check allows public access.
     */
    public function testGetItemsPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_item_permissions_check allows public access.
     */
    public function testGetItemPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons/1');
        $request->set_param('id', 1);

        $result = $this->controller->get_item_permissions_check($request);

        $this->assertTrue($result);
    }
}
