<?php
/**
 * Tests for SearchController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\SearchController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\SermonRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test SearchController functionality.
 *
 * Tests the combined search REST API endpoint.
 */
class SearchControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var SearchController
     */
    private SearchController $controller;

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

        $this->controller = new SearchController();
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
     * Test register_routes registers GET /search route.
     */
    public function testRegisterRoutesRegistersGetSearchRoute(): void
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

        // Check that /search route was registered.
        $searchRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/search');
        $this->assertNotEmpty($searchRoute, 'GET /search route should be registered');
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

    /**
     * Test route is registered with GET method.
     */
    public function testRouteIsRegisteredWithGetMethod(): void
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

        $searchRoute = array_values(array_filter($registeredRoutes, fn($r) => $r['route'] === '/search'));
        $this->assertNotEmpty($searchRoute);

        // Check that GET method is registered.
        $hasGet = false;
        foreach ($searchRoute[0]['args'] as $arg) {
            if (isset($arg['methods']) && $arg['methods'] === 'GET') {
                $hasGet = true;
                break;
            }
        }
        $this->assertTrue($hasGet, 'Route should support GET method');
    }

    // =========================================================================
    // Permission Tests
    // =========================================================================

    /**
     * Test search allows public access.
     */
    public function testSearchAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // Search Query Tests
    // =========================================================================

    /**
     * Test search with q parameter searches titles.
     */
    public function testSearchWithQParameterSearchesTitles(): void
    {
        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'The Gospel of Grace',
                'preacher_name' => 'John Doe',
                'series_name' => 'Romans',
                'service_name' => 'Sunday AM',
            ],
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['title' => 'Gospel'], 10, 0)
            ->andReturn($sermons);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with(['title' => 'Gospel'])
            ->andReturn(1);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('q', 'Gospel');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('The Gospel of Grace', $data[0]['title']);
    }

    /**
     * Test search with preacher filter.
     */
    public function testSearchWithPreacherFilter(): void
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

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('preacher', 5);
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $this->controller->get_items($request);

        // Test passes if mock expectations are met.
        $this->assertTrue(true);
    }

    /**
     * Test search with series filter.
     */
    public function testSearchWithSeriesFilter(): void
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

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('series', 3);
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $this->controller->get_items($request);

        // Test passes if mock expectations are met.
        $this->assertTrue(true);
    }

    /**
     * Test search with service filter.
     */
    public function testSearchWithServiceFilter(): void
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

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('service', 2);
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $this->controller->get_items($request);

        // Test passes if mock expectations are met.
        $this->assertTrue(true);
    }

    /**
     * Test search combines text search with filters.
     */
    public function testSearchCombinesTextSearchWithFilters(): void
    {
        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'Grace and Mercy',
                'preacher_name' => 'John Doe',
                'preacher_id' => 5,
                'series_id' => 3,
                'service_id' => 2,
            ],
        ];

        $expectedFilter = [
            'title' => 'Grace',
            'preacher_id' => 5,
            'series_id' => 3,
            'service_id' => 2,
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with($expectedFilter, 10, 0)
            ->andReturn($sermons);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with($expectedFilter)
            ->andReturn(1);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('q', 'Grace');
        $request->set_param('preacher', 5);
        $request->set_param('series', 3);
        $request->set_param('service', 2);
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
    }

    // =========================================================================
    // Pagination Tests
    // =========================================================================

    /**
     * Test search applies pagination.
     */
    public function testSearchAppliesPagination(): void
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
            ->andReturn(25);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('page', 3);
        $request->set_param('per_page', 5);

        $response = $this->controller->get_items($request);

        $headers = $response->get_headers();
        $this->assertEquals(25, $headers['X-WP-Total']);
        $this->assertEquals(5, $headers['X-WP-TotalPages']);
    }

    /**
     * Test search uses default pagination.
     */
    public function testSearchUsesDefaultPagination(): void
    {
        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->with([], 10, 0)
            ->andReturn([]);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->with([])
            ->andReturn(0);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        // No page or per_page set - should use defaults.

        $this->controller->get_items($request);

        // Test passes if mock expectations are met with defaults (10, 0).
        $this->assertTrue(true);
    }

    // =========================================================================
    // Response Format Tests
    // =========================================================================

    /**
     * Test search returns sermon data with relations.
     */
    public function testSearchReturnsSermonDataWithRelations(): void
    {
        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'preacher_id' => 5,
                'series_id' => 3,
                'service_id' => 2,
                'preacher_name' => 'John Doe',
                'series_name' => 'Romans',
                'service_name' => 'Sunday AM',
                'datetime' => '2024-01-15 10:00:00',
                'description' => 'A great sermon',
            ],
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->andReturn($sermons);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->andReturn(1);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['id']);
        $this->assertEquals('Test Sermon', $data[0]['title']);
        $this->assertEquals('John Doe', $data[0]['preacher_name']);
        $this->assertEquals('Romans', $data[0]['series_name']);
        $this->assertEquals('Sunday AM', $data[0]['service_name']);
    }

    /**
     * Test search returns empty array when no results.
     */
    public function testSearchReturnsEmptyArrayWhenNoResults(): void
    {
        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->andReturn([]);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->andReturn(0);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('q', 'NonExistentSermon');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /**
     * Test search converts IDs to integers.
     */
    public function testSearchConvertsIdsToIntegers(): void
    {
        $sermons = [
            (object) [
                'id' => '1',
                'title' => 'Test Sermon',
                'preacher_id' => '5',
                'series_id' => '3',
                'service_id' => '2',
            ],
        ];

        $this->mockRepository
            ->shouldReceive('findAllWithRelations')
            ->once()
            ->andReturn($sermons);

        $this->mockRepository
            ->shouldReceive('countFiltered')
            ->once()
            ->andReturn(1);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/search');
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertIsInt($data[0]['id']);
        $this->assertIsInt($data[0]['preacher_id']);
        $this->assertIsInt($data[0]['series_id']);
        $this->assertIsInt($data[0]['service_id']);
    }
}
