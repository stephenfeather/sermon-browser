<?php

/**
 * Tests for SeriesController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\SeriesController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\SeriesRepository;
use SermonBrowser\Repositories\SermonRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test SeriesController functionality.
 *
 * Tests the REST API endpoints for series.
 */
class SeriesControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var SeriesController
     */
    private SeriesController $controller;

    /**
     * Mock series repository.
     *
     * @var \Mockery\MockInterface&SeriesRepository
     */
    private $mockSeriesRepository;

    /**
     * Mock sermon repository.
     *
     * @var \Mockery\MockInterface&SermonRepository
     */
    private $mockSermonRepository;

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

        // Set up mock repositories.
        $this->mockSeriesRepository = Mockery::mock(SeriesRepository::class);
        $this->mockSermonRepository = Mockery::mock(SermonRepository::class);

        // Get container and inject mocks.
        $this->container = Container::getInstance();
        $this->container->set(SeriesRepository::class, $this->mockSeriesRepository);
        $this->container->set(SermonRepository::class, $this->mockSermonRepository);

        $this->controller = new SeriesController();
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
     * Test register_routes registers GET /series route.
     */
    public function testRegisterRoutesRegistersGetSeriesRoute(): void
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

        // Check that /series route was registered.
        $seriesRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/series');
        $this->assertNotEmpty($seriesRoute, 'GET /series route should be registered');
    }

    /**
     * Test register_routes registers GET /series/{id} route.
     */
    public function testRegisterRoutesRegistersGetSingleSeriesRoute(): void
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

        // Check that /series/(?P<id>\d+) route was registered.
        $singleRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/series.*id/', $r['route']) && !preg_match('/sermons/', $r['route'])
        );
        $this->assertNotEmpty($singleRoute, 'GET /series/{id} route should be registered');
    }

    /**
     * Test register_routes registers GET /series/{id}/sermons route.
     */
    public function testRegisterRoutesRegistersSeriesSermonsRoute(): void
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

        // Check that /series/{id}/sermons route was registered.
        $sermonsRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/series.*sermons/', $r['route'])
        );
        $this->assertNotEmpty($sermonsRoute, 'GET /series/{id}/sermons route should be registered');
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
    // GET /series Tests
    // =========================================================================

    /**
     * Test get_items returns list of series.
     */
    public function testGetItemsReturnsListOfSeries(): void
    {
        $series = [
            (object) [
                'id' => 1,
                'name' => 'Romans Study',
                'page_id' => 0,
            ],
            (object) [
                'id' => 2,
                'name' => 'Genesis Study',
                'page_id' => 10,
            ],
        ];

        $this->mockSeriesRepository
            ->shouldReceive('findAllSorted')
            ->once()
            ->andReturn($series);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series');

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('Romans Study', $data[0]['name']);
    }

    /**
     * Test get_items includes sermon count.
     */
    public function testGetItemsIncludesSermonCount(): void
    {
        $series = [
            (object) [
                'id' => 1,
                'name' => 'Romans Study',
                'page_id' => 0,
                'sermon_count' => 5,
            ],
        ];

        $this->mockSeriesRepository
            ->shouldReceive('findAllSorted')
            ->once()
            ->andReturn($series);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series');

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertEquals(5, $data[0]['sermon_count']);
    }

    /**
     * Test get_items searches by name.
     */
    public function testGetItemsSearchesByName(): void
    {
        $series = [
            (object) [
                'id' => 1,
                'name' => 'Romans Study',
                'page_id' => 0,
            ],
        ];

        $this->mockSeriesRepository
            ->shouldReceive('searchByName')
            ->once()
            ->with('Romans')
            ->andReturn($series);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series');
        $request->set_param('search', 'Romans');

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('Romans Study', $data[0]['name']);
    }

    // =========================================================================
    // GET /series/{id} Tests
    // =========================================================================

    /**
     * Test get_item returns single series.
     */
    public function testGetItemReturnsSingleSeries(): void
    {
        $series = (object) [
            'id' => 1,
            'name' => 'Romans Study',
            'page_id' => 10,
        ];

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($series);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series/1');
        $request->set_param('id', 1);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Romans Study', $data['name']);
        $this->assertEquals(10, $data['page_id']);
    }

    /**
     * Test get_item returns 404 when series not found.
     */
    public function testGetItemReturns404WhenNotFound(): void
    {
        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series/999');
        $request->set_param('id', 999);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Series not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // GET /series/{id}/sermons Tests
    // =========================================================================

    /**
     * Test get_sermons returns sermons for series.
     */
    public function testGetSermonsReturnsSermonsForSeries(): void
    {
        $series = (object) [
            'id' => 1,
            'name' => 'Romans Study',
        ];

        $sermons = [
            (object) [
                'id' => 10,
                'title' => 'Romans Chapter 1',
                'series_id' => 1,
            ],
            (object) [
                'id' => 11,
                'title' => 'Romans Chapter 2',
                'series_id' => 1,
            ],
        ];

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($series);

        $this->mockSermonRepository
            ->shouldReceive('findBySeries')
            ->once()
            ->with(1, 0)
            ->andReturn($sermons);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series/1/sermons');
        $request->set_param('id', 1);

        $response = $this->controller->get_sermons($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('Romans Chapter 1', $data[0]['title']);
    }

    /**
     * Test get_sermons returns 404 when series not found.
     */
    public function testGetSermonsReturns404WhenSeriesNotFound(): void
    {
        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series/999/sermons');
        $request->set_param('id', 999);

        $response = $this->controller->get_sermons($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Series not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // POST /series Tests
    // =========================================================================

    /**
     * Test create_item creates new series.
     */
    public function testCreateItemCreatesNewSeries(): void
    {
        $newSeries = (object) [
            'id' => 10,
            'name' => 'New Series',
            'page_id' => 0,
        ];

        $this->mockSeriesRepository
            ->shouldReceive('create')
            ->once()
            ->with(['name' => 'New Series'])
            ->andReturn(10);

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($newSeries);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/series');
        $request->set_param('name', 'New Series');

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(201, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals(10, $data['id']);
        $this->assertEquals('New Series', $data['name']);
    }

    /**
     * Test create_item creates series with page_id.
     */
    public function testCreateItemCreatesSeriesWithPageId(): void
    {
        $newSeries = (object) [
            'id' => 10,
            'name' => 'New Series',
            'page_id' => 42,
        ];

        $this->mockSeriesRepository
            ->shouldReceive('create')
            ->once()
            ->with(['name' => 'New Series', 'page_id' => 42])
            ->andReturn(10);

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($newSeries);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/series');
        $request->set_param('name', 'New Series');
        $request->set_param('page_id', 42);

        $response = $this->controller->create_item($request);

        $data = $response->get_data();
        $this->assertEquals(42, $data['page_id']);
    }

    /**
     * Test create_item requires name.
     */
    public function testCreateItemRequiresName(): void
    {
        $request = new WP_REST_Request('POST', '/sermon-browser/v1/series');
        // No name set.

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

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/series');

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

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/series');

        $result = $this->controller->create_item_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // PUT /series/{id} Tests
    // =========================================================================

    /**
     * Test update_item updates existing series.
     */
    public function testUpdateItemUpdatesExistingSeries(): void
    {
        $existingSeries = (object) [
            'id' => 5,
            'name' => 'Original Name',
            'page_id' => 0,
        ];

        $updatedSeries = (object) [
            'id' => 5,
            'name' => 'Updated Name',
            'page_id' => 0,
        ];

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingSeries);

        $this->mockSeriesRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Updated Name'])
            ->andReturn(true);

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($updatedSeries);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/series/5');
        $request->set_param('id', 5);
        $request->set_param('name', 'Updated Name');

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Updated Name', $data['name']);
    }

    /**
     * Test update_item updates page_id.
     */
    public function testUpdateItemUpdatesPageId(): void
    {
        $existingSeries = (object) [
            'id' => 5,
            'name' => 'Series Name',
            'page_id' => 0,
        ];

        $updatedSeries = (object) [
            'id' => 5,
            'name' => 'Series Name',
            'page_id' => 99,
        ];

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingSeries);

        $this->mockSeriesRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['page_id' => 99])
            ->andReturn(true);

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($updatedSeries);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/series/5');
        $request->set_param('id', 5);
        $request->set_param('page_id', 99);

        $response = $this->controller->update_item($request);

        $data = $response->get_data();
        $this->assertEquals(99, $data['page_id']);
    }

    /**
     * Test update_item returns 404 when series not found.
     */
    public function testUpdateItemReturns404WhenNotFound(): void
    {
        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/series/999');
        $request->set_param('id', 999);
        $request->set_param('name', 'Updated Name');

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

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/series/5');
        $request->set_param('id', 5);

        $result = $this->controller->update_item_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    // =========================================================================
    // DELETE /series/{id} Tests
    // =========================================================================

    /**
     * Test delete_item deletes series.
     */
    public function testDeleteItemDeletesSeries(): void
    {
        $existingSeries = (object) [
            'id' => 5,
            'name' => 'To Be Deleted',
            'page_id' => 0,
        ];

        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingSeries);

        $this->mockSeriesRepository
            ->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(true);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/series/5');
        $request->set_param('id', 5);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertTrue($data['deleted']);
    }

    /**
     * Test delete_item returns 404 when series not found.
     */
    public function testDeleteItemReturns404WhenNotFound(): void
    {
        $this->mockSeriesRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/series/999');
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

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/series/5');
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
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_item_permissions_check allows public access.
     */
    public function testGetItemPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series/1');
        $request->set_param('id', 1);

        $result = $this->controller->get_item_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_sermons_permissions_check allows public access.
     */
    public function testGetSermonsPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/series/1/sermons');
        $request->set_param('id', 1);

        $result = $this->controller->get_sermons_permissions_check($request);

        $this->assertTrue($result);
    }
}
