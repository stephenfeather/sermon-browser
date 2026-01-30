<?php
/**
 * Tests for ServicesController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\ServicesController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\ServiceRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test ServicesController functionality.
 *
 * Tests the REST API endpoints for services.
 */
class ServicesControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var ServicesController
     */
    private ServicesController $controller;

    /**
     * Mock repository.
     *
     * @var \Mockery\MockInterface&ServiceRepository
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
        $this->mockRepository = Mockery::mock(ServiceRepository::class);

        // Get container and inject mock.
        $this->container = Container::getInstance();
        $this->container->set(ServiceRepository::class, $this->mockRepository);

        $this->controller = new ServicesController();
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
     * Test register_routes registers GET /services route.
     */
    public function testRegisterRoutesRegistersGetServicesRoute(): void
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

        // Check that /services route was registered.
        $servicesRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/services');
        $this->assertNotEmpty($servicesRoute, 'GET /services route should be registered');
    }

    /**
     * Test register_routes registers GET /services/{id} route.
     */
    public function testRegisterRoutesRegistersGetSingleServiceRoute(): void
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

        // Check that /services/(?P<id>\d+) route was registered.
        $singleRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/services.*id/', $r['route'])
        );
        $this->assertNotEmpty($singleRoute, 'GET /services/{id} route should be registered');
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
    // GET /services Tests
    // =========================================================================

    /**
     * Test get_items returns list of services.
     */
    public function testGetItemsReturnsListOfServices(): void
    {
        $services = [
            (object) [
                'id' => 1,
                'name' => 'Sunday Morning',
                'time' => '10:00',
            ],
            (object) [
                'id' => 2,
                'name' => 'Sunday Evening',
                'time' => '18:00',
            ],
        ];

        $this->mockRepository
            ->shouldReceive('findAllSorted')
            ->once()
            ->andReturn($services);

        $this->mockRepository
            ->shouldReceive('count')
            ->once()
            ->with([])
            ->andReturn(2);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/services');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('Sunday Morning', $data[0]['name']);
    }

    /**
     * Test get_items returns pagination headers.
     */
    public function testGetItemsReturnsPaginationHeaders(): void
    {
        $services = [];

        $this->mockRepository
            ->shouldReceive('findAllSorted')
            ->once()
            ->andReturn($services);

        $this->mockRepository
            ->shouldReceive('count')
            ->once()
            ->with([])
            ->andReturn(15);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/services');
        $request->set_param('page', 1);
        $request->set_param('per_page', 5);

        $response = $this->controller->get_items($request);

        $headers = $response->get_headers();
        $this->assertEquals(15, $headers['X-WP-Total']);
        $this->assertEquals(3, $headers['X-WP-TotalPages']);
    }

    // =========================================================================
    // GET /services/{id} Tests
    // =========================================================================

    /**
     * Test get_item returns single service.
     */
    public function testGetItemReturnsSingleService(): void
    {
        $service = (object) [
            'id' => 1,
            'name' => 'Sunday Morning',
            'time' => '10:00',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($service);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/services/1');
        $request->set_param('id', 1);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Sunday Morning', $data['name']);
        $this->assertEquals('10:00', $data['time']);
    }

    /**
     * Test get_item returns 404 when service not found.
     */
    public function testGetItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/services/999');
        $request->set_param('id', 999);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Service not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // POST /services Tests
    // =========================================================================

    /**
     * Test create_item creates new service.
     */
    public function testCreateItemCreatesNewService(): void
    {
        $newService = (object) [
            'id' => 10,
            'name' => 'Wednesday Bible Study',
            'time' => '19:00',
        ];

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Wednesday Bible Study',
                'time' => '19:00',
            ])
            ->andReturn(10);

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($newService);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/services');
        $request->set_param('name', 'Wednesday Bible Study');
        $request->set_param('time', '19:00');

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(201, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals(10, $data['id']);
        $this->assertEquals('Wednesday Bible Study', $data['name']);
    }

    /**
     * Test create_item requires name.
     */
    public function testCreateItemRequiresName(): void
    {
        $request = new WP_REST_Request('POST', '/sermon-browser/v1/services');
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

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/services');

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

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/services');

        $result = $this->controller->create_item_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // PUT /services/{id} Tests
    // =========================================================================

    /**
     * Test update_item updates existing service.
     */
    public function testUpdateItemUpdatesExistingService(): void
    {
        $existingService = (object) [
            'id' => 5,
            'name' => 'Sunday Morning',
            'time' => '10:00',
        ];

        $updatedService = (object) [
            'id' => 5,
            'name' => 'Sunday Worship',
            'time' => '10:30',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingService);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Sunday Worship', 'time' => '10:30'])
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($updatedService);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/services/5');
        $request->set_param('id', 5);
        $request->set_param('name', 'Sunday Worship');
        $request->set_param('time', '10:30');

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Sunday Worship', $data['name']);
    }

    /**
     * Test update_item returns 404 when service not found.
     */
    public function testUpdateItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/services/999');
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

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/services/5');
        $request->set_param('id', 5);

        $result = $this->controller->update_item_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    // =========================================================================
    // DELETE /services/{id} Tests
    // =========================================================================

    /**
     * Test delete_item deletes service.
     */
    public function testDeleteItemDeletesService(): void
    {
        $existingService = (object) [
            'id' => 5,
            'name' => 'To Be Deleted',
            'time' => '10:00',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingService);

        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(true);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/services/5');
        $request->set_param('id', 5);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertTrue($data['deleted']);
    }

    /**
     * Test delete_item returns 404 when service not found.
     */
    public function testDeleteItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/services/999');
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

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/services/5');
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
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/services');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_item_permissions_check allows public access.
     */
    public function testGetItemPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/services/1');
        $request->set_param('id', 1);

        $result = $this->controller->get_item_permissions_check($request);

        $this->assertTrue($result);
    }
}
