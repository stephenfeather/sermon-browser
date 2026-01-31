<?php

/**
 * Tests for PreachersController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\PreachersController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\PreacherRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test PreachersController functionality.
 *
 * Tests the REST API endpoints for preachers.
 */
class PreachersControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var PreachersController
     */
    private PreachersController $controller;

    /**
     * Mock repository.
     *
     * @var \Mockery\MockInterface&PreacherRepository
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
        $this->mockRepository = Mockery::mock(PreacherRepository::class);

        // Get container and inject mock.
        $this->container = Container::getInstance();
        $this->container->set(PreacherRepository::class, $this->mockRepository);

        $this->controller = new PreachersController();
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
     * Test register_routes registers GET /preachers route.
     */
    public function testRegisterRoutesRegistersGetPreachersRoute(): void
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

        // Check that /preachers route was registered.
        $preachersRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/preachers');
        $this->assertNotEmpty($preachersRoute, 'GET /preachers route should be registered');
    }

    /**
     * Test register_routes registers GET /preachers/{id} route.
     */
    public function testRegisterRoutesRegistersGetSinglePreacherRoute(): void
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

        // Check that /preachers/(?P<id>\d+) route was registered.
        $singleRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/preachers.*id/', $r['route'])
        );
        $this->assertNotEmpty($singleRoute, 'GET /preachers/{id} route should be registered');
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
    // GET /preachers Tests
    // =========================================================================

    /**
     * Test get_items returns list of preachers with sermon counts.
     */
    public function testGetItemsReturnsListOfPreachersWithSermonCounts(): void
    {
        $preachers = [
            (object) [
                'id' => 1,
                'name' => 'John Doe',
                'description' => 'Senior Pastor',
                'image' => 'john.jpg',
                'sermon_count' => 15,
            ],
            (object) [
                'id' => 2,
                'name' => 'Jane Smith',
                'description' => 'Associate Pastor',
                'image' => 'jane.jpg',
                'sermon_count' => 8,
            ],
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($preachers);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers');

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('John Doe', $data[0]['name']);
        $this->assertEquals(15, $data[0]['sermon_count']);
    }

    /**
     * Test get_items sets pagination headers.
     */
    public function testGetItemsSetsPaginationHeaders(): void
    {
        $preachers = [
            (object) ['id' => 1, 'name' => 'John Doe', 'sermon_count' => 5],
            (object) ['id' => 2, 'name' => 'Jane Smith', 'sermon_count' => 3],
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($preachers);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers');

        $response = $this->controller->get_items($request);

        $headers = $response->get_headers();
        $this->assertEquals(2, $headers['X-WP-Total']);
        $this->assertEquals(1, $headers['X-WP-TotalPages']);
    }

    /**
     * Test get_items searches by name when search param provided.
     */
    public function testGetItemsSearchesByName(): void
    {
        $preachers = [
            (object) [
                'id' => 1,
                'name' => 'John Doe',
                'description' => 'Senior Pastor',
                'sermon_count' => 15,
            ],
        ];

        $this->mockRepository
            ->shouldReceive('searchByName')
            ->once()
            ->with('John')
            ->andReturn($preachers);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers');
        $request->set_param('search', 'John');

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('John Doe', $data[0]['name']);
    }

    // =========================================================================
    // GET /preachers/{id} Tests
    // =========================================================================

    /**
     * Test get_item returns single preacher.
     */
    public function testGetItemReturnsSinglePreacher(): void
    {
        $preacher = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'description' => 'Senior Pastor',
            'image' => 'john.jpg',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($preacher);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers/1');
        $request->set_param('id', 1);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('John Doe', $data['name']);
        $this->assertEquals('Senior Pastor', $data['description']);
    }

    /**
     * Test get_item returns 404 when preacher not found.
     */
    public function testGetItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers/999');
        $request->set_param('id', 999);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Preacher not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // POST /preachers Tests
    // =========================================================================

    /**
     * Test create_item creates new preacher.
     */
    public function testCreateItemCreatesNewPreacher(): void
    {
        $newPreacher = (object) [
            'id' => 10,
            'name' => 'New Preacher',
            'description' => 'Youth Pastor',
            'image' => '',
        ];

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'New Preacher',
                'description' => 'Youth Pastor',
            ])
            ->andReturn(10);

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($newPreacher);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/preachers');
        $request->set_param('name', 'New Preacher');
        $request->set_param('description', 'Youth Pastor');

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(201, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals(10, $data['id']);
        $this->assertEquals('New Preacher', $data['name']);
    }

    /**
     * Test create_item requires name.
     */
    public function testCreateItemRequiresName(): void
    {
        $request = new WP_REST_Request('POST', '/sermon-browser/v1/preachers');
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

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/preachers');

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

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/preachers');

        $result = $this->controller->create_item_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // PUT /preachers/{id} Tests
    // =========================================================================

    /**
     * Test update_item updates existing preacher.
     */
    public function testUpdateItemUpdatesExistingPreacher(): void
    {
        $existingPreacher = (object) [
            'id' => 5,
            'name' => 'Original Name',
            'description' => 'Original Description',
        ];

        $updatedPreacher = (object) [
            'id' => 5,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'image' => '',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingPreacher);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Updated Name', 'description' => 'Updated Description'])
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($updatedPreacher);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/preachers/5');
        $request->set_param('id', 5);
        $request->set_param('name', 'Updated Name');
        $request->set_param('description', 'Updated Description');

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('Updated Name', $data['name']);
    }

    /**
     * Test update_item returns 404 when preacher not found.
     */
    public function testUpdateItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/preachers/999');
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

        $request = new WP_REST_Request('PUT', '/sermon-browser/v1/preachers/5');
        $request->set_param('id', 5);

        $result = $this->controller->update_item_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    // =========================================================================
    // DELETE /preachers/{id} Tests
    // =========================================================================

    /**
     * Test delete_item deletes preacher.
     */
    public function testDeleteItemDeletesPreacher(): void
    {
        $existingPreacher = (object) [
            'id' => 5,
            'name' => 'To Be Deleted',
            'description' => 'Will be removed',
        ];

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingPreacher);

        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(true);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/preachers/5');
        $request->set_param('id', 5);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertTrue($data['deleted']);
    }

    /**
     * Test delete_item returns 404 when preacher not found.
     */
    public function testDeleteItemReturns404WhenNotFound(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/preachers/999');
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

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/preachers/5');
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
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_item_permissions_check allows public access.
     */
    public function testGetItemPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/preachers/1');
        $request->set_param('id', 1);

        $result = $this->controller->get_item_permissions_check($request);

        $this->assertTrue($result);
    }
}
