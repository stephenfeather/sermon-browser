<?php
/**
 * Tests for TagsController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\TagsController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\TagRepository;
use SermonBrowser\Repositories\SermonRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test TagsController functionality.
 *
 * Tests the REST API endpoints for tags.
 */
class TagsControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var TagsController
     */
    private TagsController $controller;

    /**
     * Mock tag repository.
     *
     * @var \Mockery\MockInterface&TagRepository
     */
    private $mockTagRepository;

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
        $this->mockTagRepository = Mockery::mock(TagRepository::class);
        $this->mockSermonRepository = Mockery::mock(SermonRepository::class);

        // Get container and inject mocks.
        $this->container = Container::getInstance();
        $this->container->set(TagRepository::class, $this->mockTagRepository);
        $this->container->set(SermonRepository::class, $this->mockSermonRepository);

        $this->controller = new TagsController();
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
     * Test register_routes registers GET /tags route.
     */
    public function testRegisterRoutesRegistersGetTagsRoute(): void
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

        // Check that /tags route was registered.
        $tagsRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/tags');
        $this->assertNotEmpty($tagsRoute, 'GET /tags route should be registered');
    }

    /**
     * Test register_routes registers GET /tags/{name}/sermons route.
     */
    public function testRegisterRoutesRegistersGetSermonsByTagRoute(): void
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

        // Check that /tags/{name}/sermons route was registered.
        $sermonsRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/tags.*name.*sermons/', $r['route'])
        );
        $this->assertNotEmpty($sermonsRoute, 'GET /tags/{name}/sermons route should be registered');
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
    // GET /tags Tests (Tag Cloud)
    // =========================================================================

    /**
     * Test get_items returns list of tags with sermon counts.
     */
    public function testGetItemsReturnsListOfTagsWithSermonCounts(): void
    {
        $tags = [
            (object) [
                'id' => 1,
                'name' => 'Faith',
                'sermon_count' => 15,
            ],
            (object) [
                'id' => 2,
                'name' => 'Hope',
                'sermon_count' => 8,
            ],
            (object) [
                'id' => 3,
                'name' => 'Love',
                'sermon_count' => 12,
            ],
        ];

        $this->mockTagRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->with(0)
            ->andReturn($tags);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(3, $data);
        $this->assertEquals('Faith', $data[0]['name']);
        $this->assertEquals(15, $data[0]['sermon_count']);
    }

    /**
     * Test get_items respects limit parameter.
     */
    public function testGetItemsRespectsLimitParameter(): void
    {
        $tags = [
            (object) [
                'id' => 1,
                'name' => 'Faith',
                'sermon_count' => 15,
            ],
            (object) [
                'id' => 2,
                'name' => 'Hope',
                'sermon_count' => 8,
            ],
        ];

        $this->mockTagRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->with(2)
            ->andReturn($tags);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');
        $request->set_param('limit', 2);

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(2, $data);
    }

    /**
     * Test get_items sets pagination headers.
     */
    public function testGetItemsSetsPaginationHeaders(): void
    {
        $tags = [
            (object) ['id' => 1, 'name' => 'Faith', 'sermon_count' => 5],
            (object) ['id' => 2, 'name' => 'Hope', 'sermon_count' => 3],
        ];

        $this->mockTagRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($tags);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');

        $response = $this->controller->get_items($request);

        $headers = $response->get_headers();
        $this->assertEquals(2, $headers['X-WP-Total']);
        $this->assertEquals(1, $headers['X-WP-TotalPages']);
    }

    /**
     * Test get_items returns empty array when no tags.
     */
    public function testGetItemsReturnsEmptyArrayWhenNoTags(): void
    {
        $this->mockTagRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn([]);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    // =========================================================================
    // GET /tags/{name}/sermons Tests
    // =========================================================================

    /**
     * Test get_sermons_by_tag returns sermons for a tag.
     */
    public function testGetSermonsByTagReturnsSermons(): void
    {
        $tag = (object) [
            'id' => 1,
            'name' => 'Faith',
        ];

        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'Walking by Faith',
                'preacher_name' => 'John Doe',
                'series_name' => 'Faith Series',
                'service_name' => 'Sunday Morning',
            ],
            (object) [
                'id' => 2,
                'title' => 'Living by Faith',
                'preacher_name' => 'Jane Smith',
                'series_name' => 'Faith Series',
                'service_name' => 'Sunday Evening',
            ],
        ];

        $this->mockTagRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('Faith')
            ->andReturn($tag);

        $this->mockTagRepository
            ->shouldReceive('getSermonIdsByTag')
            ->once()
            ->with(1)
            ->andReturn([1, 2]);

        $this->mockSermonRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(1)
            ->andReturn($sermons[0]);

        $this->mockSermonRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(2)
            ->andReturn($sermons[1]);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags/Faith/sermons');
        $request->set_param('name', 'Faith');

        $response = $this->controller->get_sermons_by_tag($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('Walking by Faith', $data[0]['title']);
        $this->assertEquals('Living by Faith', $data[1]['title']);
    }

    /**
     * Test get_sermons_by_tag returns 404 when tag not found.
     */
    public function testGetSermonsByTagReturns404WhenTagNotFound(): void
    {
        $this->mockTagRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('NonexistentTag')
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags/NonexistentTag/sermons');
        $request->set_param('name', 'NonexistentTag');

        $response = $this->controller->get_sermons_by_tag($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Tag not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    /**
     * Test get_sermons_by_tag returns empty array when no sermons.
     */
    public function testGetSermonsByTagReturnsEmptyArrayWhenNoSermons(): void
    {
        $tag = (object) [
            'id' => 1,
            'name' => 'EmptyTag',
        ];

        $this->mockTagRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('EmptyTag')
            ->andReturn($tag);

        $this->mockTagRepository
            ->shouldReceive('getSermonIdsByTag')
            ->once()
            ->with(1)
            ->andReturn([]);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags/EmptyTag/sermons');
        $request->set_param('name', 'EmptyTag');

        $response = $this->controller->get_sermons_by_tag($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    /**
     * Test get_sermons_by_tag sets pagination headers.
     */
    public function testGetSermonsByTagSetsPaginationHeaders(): void
    {
        $tag = (object) [
            'id' => 1,
            'name' => 'Faith',
        ];

        $sermon = (object) [
            'id' => 1,
            'title' => 'Walking by Faith',
        ];

        $this->mockTagRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('Faith')
            ->andReturn($tag);

        $this->mockTagRepository
            ->shouldReceive('getSermonIdsByTag')
            ->once()
            ->with(1)
            ->andReturn([1]);

        $this->mockSermonRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(1)
            ->andReturn($sermon);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags/Faith/sermons');
        $request->set_param('name', 'Faith');

        $response = $this->controller->get_sermons_by_tag($request);

        $headers = $response->get_headers();
        $this->assertEquals(1, $headers['X-WP-Total']);
        $this->assertEquals(1, $headers['X-WP-TotalPages']);
    }

    /**
     * Test get_sermons_by_tag handles URL-encoded tag names.
     */
    public function testGetSermonsByTagHandlesUrlEncodedNames(): void
    {
        $tag = (object) [
            'id' => 1,
            'name' => 'Faith & Works',
        ];

        $this->mockTagRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('Faith & Works')
            ->andReturn($tag);

        $this->mockTagRepository
            ->shouldReceive('getSermonIdsByTag')
            ->once()
            ->with(1)
            ->andReturn([]);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags/Faith%20%26%20Works/sermons');
        $request->set_param('name', 'Faith & Works');

        $response = $this->controller->get_sermons_by_tag($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
    }

    // =========================================================================
    // Public Access Tests
    // =========================================================================

    /**
     * Test get_items_permissions_check allows public access.
     */
    public function testGetItemsPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_sermons_by_tag_permissions_check allows public access.
     */
    public function testGetSermonsByTagPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags/Faith/sermons');
        $request->set_param('name', 'Faith');

        $result = $this->controller->get_sermons_by_tag_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // Response Format Tests
    // =========================================================================

    /**
     * Test tag response contains required fields.
     */
    public function testTagResponseContainsRequiredFields(): void
    {
        $tags = [
            (object) [
                'id' => 1,
                'name' => 'Faith',
                'sermon_count' => 15,
            ],
        ];

        $this->mockTagRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($tags);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');

        $response = $this->controller->get_items($request);
        $data = $response->get_data();

        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('sermon_count', $data[0]);
    }

    /**
     * Test numeric fields are properly typed.
     */
    public function testNumericFieldsAreProperlyTyped(): void
    {
        $tags = [
            (object) [
                'id' => '1',
                'name' => 'Faith',
                'sermon_count' => '15',
            ],
        ];

        $this->mockTagRepository
            ->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($tags);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/tags');

        $response = $this->controller->get_items($request);
        $data = $response->get_data();

        $this->assertIsInt($data[0]['id']);
        $this->assertIsInt($data[0]['sermon_count']);
    }
}
