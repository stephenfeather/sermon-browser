<?php

/**
 * Tests for FilesController.
 *
 * @package SermonBrowser\Tests\Unit\REST\Endpoints
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST\Endpoints;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\Endpoints\FilesController;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Repositories\SermonRepository;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;

/**
 * Test FilesController functionality.
 *
 * Tests the REST API endpoints for files.
 */
class FilesControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var FilesController
     */
    private FilesController $controller;

    /**
     * Mock file repository.
     *
     * @var \Mockery\MockInterface&FileRepository
     */
    private $mockFileRepository;

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
        $this->mockFileRepository = Mockery::mock(FileRepository::class);
        $this->mockSermonRepository = Mockery::mock(SermonRepository::class);

        // Get container and inject mocks.
        $this->container = Container::getInstance();
        $this->container->set(FileRepository::class, $this->mockFileRepository);
        $this->container->set(SermonRepository::class, $this->mockSermonRepository);

        $this->controller = new FilesController();
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
     * Test register_routes registers GET /files route.
     */
    public function testRegisterRoutesRegistersGetFilesRoute(): void
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

        // Check that /files route was registered.
        $filesRoute = array_filter($registeredRoutes, fn($r) => $r['route'] === '/files');
        $this->assertNotEmpty($filesRoute, 'GET /files route should be registered');
    }

    /**
     * Test register_routes registers GET /files/{id} route.
     */
    public function testRegisterRoutesRegistersGetSingleFileRoute(): void
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

        // Check that /files/(?P<id>\d+) route was registered.
        $singleRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/^\/files\/\(\?P<id>/', $r['route'])
        );
        $this->assertNotEmpty($singleRoute, 'GET /files/{id} route should be registered');
    }

    /**
     * Test register_routes registers GET /sermons/{id}/files route.
     */
    public function testRegisterRoutesRegistersGetSermonFilesRoute(): void
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

        // Check that /sermons/{id}/files route was registered.
        $sermonFilesRoute = array_filter(
            $registeredRoutes,
            fn($r) => preg_match('/sermons.*files/', $r['route'])
        );
        $this->assertNotEmpty($sermonFilesRoute, 'GET /sermons/{id}/files route should be registered');
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
    // GET /files Tests
    // =========================================================================

    /**
     * Test get_items returns list of files.
     */
    public function testGetItemsReturnsListOfFiles(): void
    {
        $files = [
            (object) [
                'id' => 1,
                'name' => 'sermon-audio.mp3',
                'type' => 'mp3',
                'sermon_id' => 10,
                'count' => 25,
                'duration' => 3600,
            ],
            (object) [
                'id' => 2,
                'name' => 'sermon-notes.pdf',
                'type' => 'pdf',
                'sermon_id' => 10,
                'count' => 15,
                'duration' => 0,
            ],
        ];

        $this->mockFileRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($files);

        $this->mockFileRepository
            ->shouldReceive('count')
            ->once()
            ->andReturn(2);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files');

        $response = $this->controller->get_items($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
        $this->assertEquals('sermon-audio.mp3', $data[0]['name']);
        $this->assertEquals('mp3', $data[0]['type']);
    }

    /**
     * Test get_items sets pagination headers.
     */
    public function testGetItemsSetsPaginationHeaders(): void
    {
        $files = [
            (object) ['id' => 1, 'name' => 'sermon-audio.mp3', 'type' => 'mp3', 'sermon_id' => 10, 'count' => 5],
            (object) ['id' => 2, 'name' => 'sermon-notes.pdf', 'type' => 'pdf', 'sermon_id' => 10, 'count' => 3],
        ];

        $this->mockFileRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($files);

        $this->mockFileRepository
            ->shouldReceive('count')
            ->once()
            ->andReturn(2);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files');

        $response = $this->controller->get_items($request);

        $headers = $response->get_headers();
        $this->assertEquals(2, $headers['X-WP-Total']);
        $this->assertEquals(1, $headers['X-WP-TotalPages']);
    }

    /**
     * Test get_items filters by type when type param provided.
     */
    public function testGetItemsFiltersByType(): void
    {
        $files = [
            (object) [
                'id' => 1,
                'name' => 'sermon-audio.mp3',
                'type' => 'mp3',
                'sermon_id' => 10,
                'count' => 25,
            ],
        ];

        $this->mockFileRepository
            ->shouldReceive('findByType')
            ->once()
            ->with('mp3')
            ->andReturn($files);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files');
        $request->set_param('type', 'mp3');

        $response = $this->controller->get_items($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('mp3', $data[0]['type']);
    }

    // =========================================================================
    // GET /files/{id} Tests
    // =========================================================================

    /**
     * Test get_item returns single file.
     */
    public function testGetItemReturnsSingleFile(): void
    {
        $file = (object) [
            'id' => 1,
            'name' => 'sermon-audio.mp3',
            'type' => 'mp3',
            'sermon_id' => 10,
            'count' => 25,
            'duration' => 3600,
        ];

        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($file);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files/1');
        $request->set_param('id', 1);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals('sermon-audio.mp3', $data['name']);
        $this->assertEquals('mp3', $data['type']);
        $this->assertEquals(25, $data['count']);
    }

    /**
     * Test get_item returns 404 when file not found.
     */
    public function testGetItemReturns404WhenNotFound(): void
    {
        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files/999');
        $request->set_param('id', 999);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('File not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // GET /sermons/{id}/files Tests
    // =========================================================================

    /**
     * Test get_sermon_files returns files for a sermon.
     */
    public function testGetSermonFilesReturnsFilesForSermon(): void
    {
        $sermon = (object) ['id' => 10, 'title' => 'Test Sermon'];
        $files = [
            (object) [
                'id' => 1,
                'name' => 'sermon-audio.mp3',
                'type' => 'mp3',
                'sermon_id' => 10,
                'count' => 25,
            ],
            (object) [
                'id' => 2,
                'name' => 'sermon-notes.pdf',
                'type' => 'pdf',
                'sermon_id' => 10,
                'count' => 15,
            ],
        ];

        $this->mockSermonRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($sermon);

        $this->mockFileRepository
            ->shouldReceive('findBySermon')
            ->once()
            ->with(10)
            ->andReturn($files);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);

        $response = $this->controller->get_sermon_files($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(2, $data);
    }

    /**
     * Test get_sermon_files returns 404 when sermon not found.
     */
    public function testGetSermonFilesReturns404WhenSermonNotFound(): void
    {
        $this->mockSermonRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons/999/files');
        $request->set_param('sermon_id', 999);

        $response = $this->controller->get_sermon_files($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Sermon not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    // =========================================================================
    // POST /sermons/{id}/files Tests
    // =========================================================================

    /**
     * Test attach_file_to_sermon links file to sermon.
     */
    public function testAttachFileToSermonLinksFileToSermon(): void
    {
        $sermon = (object) ['id' => 10, 'title' => 'Test Sermon'];
        $file = (object) [
            'id' => 1,
            'name' => 'sermon-audio.mp3',
            'type' => 'mp3',
            'sermon_id' => 0,
            'count' => 0,
        ];
        $updatedFile = (object) [
            'id' => 1,
            'name' => 'sermon-audio.mp3',
            'type' => 'mp3',
            'sermon_id' => 10,
            'count' => 0,
        ];

        $this->mockSermonRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($sermon);

        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($file);

        $this->mockFileRepository
            ->shouldReceive('linkToSermon')
            ->once()
            ->with(1, 10)
            ->andReturn(true);

        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($updatedFile);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);
        $request->set_param('file_id', 1);

        $response = $this->controller->attach_file_to_sermon($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertEquals(10, $data['sermon_id']);
    }

    /**
     * Test attach_file_to_sermon returns 404 when sermon not found.
     */
    public function testAttachFileToSermonReturns404WhenSermonNotFound(): void
    {
        $this->mockSermonRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons/999/files');
        $request->set_param('sermon_id', 999);
        $request->set_param('file_id', 1);

        $response = $this->controller->attach_file_to_sermon($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('Sermon not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    /**
     * Test attach_file_to_sermon returns 404 when file not found.
     */
    public function testAttachFileToSermonReturns404WhenFileNotFound(): void
    {
        $sermon = (object) ['id' => 10, 'title' => 'Test Sermon'];

        $this->mockSermonRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($sermon);

        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);
        $request->set_param('file_id', 999);

        $response = $this->controller->attach_file_to_sermon($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('File not found.', $response->get_error_message());
        $this->assertEquals(404, $response->get_error_data()['status']);
    }

    /**
     * Test attach_file_to_sermon requires file_id.
     */
    public function testAttachFileToSermonRequiresFileId(): void
    {
        $sermon = (object) ['id' => 10, 'title' => 'Test Sermon'];

        $this->mockSermonRepository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($sermon);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);
        // No file_id set.

        $response = $this->controller->attach_file_to_sermon($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    /**
     * Test attach_file_to_sermon permission check requires edit_posts.
     */
    public function testAttachFilePermissionCheckRequiresEditPosts(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);

        $result = $this->controller->attach_file_permissions_check($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * Test attach_file_to_sermon permission check returns true when user has edit_posts.
     */
    public function testAttachFilePermissionCheckReturnsTrueWithCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $request = new WP_REST_Request('POST', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);

        $result = $this->controller->attach_file_permissions_check($request);

        $this->assertTrue($result);
    }

    // =========================================================================
    // DELETE /files/{id} Tests
    // =========================================================================

    /**
     * Test delete_item deletes file.
     */
    public function testDeleteItemDeletesFile(): void
    {
        $existingFile = (object) [
            'id' => 5,
            'name' => 'to-delete.mp3',
            'type' => 'mp3',
            'sermon_id' => 10,
            'count' => 5,
        ];

        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($existingFile);

        $this->mockFileRepository
            ->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(true);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/files/5');
        $request->set_param('id', 5);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertTrue($data['deleted']);
    }

    /**
     * Test delete_item returns 404 when file not found.
     */
    public function testDeleteItemReturns404WhenNotFound(): void
    {
        $this->mockFileRepository
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/files/999');
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

        $request = new WP_REST_Request('DELETE', '/sermon-browser/v1/files/5');
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
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files');

        $result = $this->controller->get_items_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_item_permissions_check allows public access.
     */
    public function testGetItemPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/files/1');
        $request->set_param('id', 1);

        $result = $this->controller->get_item_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_sermon_files_permissions_check allows public access.
     */
    public function testGetSermonFilesPermissionCheckAllowsPublicAccess(): void
    {
        $request = new WP_REST_Request('GET', '/sermon-browser/v1/sermons/10/files');
        $request->set_param('sermon_id', 10);

        $result = $this->controller->get_sermon_files_permissions_check($request);

        $this->assertTrue($result);
    }
}
