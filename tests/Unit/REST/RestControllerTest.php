<?php
/**
 * Tests for RestController.
 *
 * @package SermonBrowser\Tests\Unit\REST
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\RestController;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;
use WP_Error;

/**
 * Test RestController functionality.
 *
 * Tests the base REST controller class that provides common
 * functionality for all REST endpoints.
 */
class RestControllerTest extends TestCase
{
    /**
     * The controller instance.
     *
     * @var RestController
     */
    private RestController $controller;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a concrete implementation for testing.
        $this->controller = new class extends RestController {
            // Expose protected methods for testing.
            public function testCheckAdminPermission(): bool
            {
                return $this->check_admin_permission();
            }

            public function testCheckEditPermission(): bool
            {
                return $this->check_edit_permission();
            }

            public function testGetCollectionParams(): array
            {
                return $this->get_collection_params();
            }

            public function testPreparePaginationResponse(
                \WP_REST_Response $response,
                int $total,
                int $perPage
            ): \WP_REST_Response {
                return $this->prepare_pagination_response($response, $total, $perPage);
            }

            public function testPrepareItemResponse(array $item): array
            {
                return $this->prepare_item_response($item);
            }

            public function testPrepareErrorResponse(string $message, int $code): \WP_Error
            {
                return $this->prepare_error_response($message, $code);
            }

            public function getNamespace(): string
            {
                return $this->namespace;
            }
        };
    }

    /**
     * Test namespace is set correctly.
     */
    public function testNamespaceIsSermonBrowserV1(): void
    {
        $this->assertEquals('sermon-browser/v1', $this->controller->getNamespace());
    }

    /**
     * Test check_admin_permission returns true when user has manage_options.
     */
    public function testCheckAdminPermissionReturnsTrueWhenUserHasCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        $this->assertTrue($this->controller->testCheckAdminPermission());
    }

    /**
     * Test check_admin_permission returns false when user lacks manage_options.
     */
    public function testCheckAdminPermissionReturnsFalseWhenUserLacksCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        $this->assertFalse($this->controller->testCheckAdminPermission());
    }

    /**
     * Test check_edit_permission returns true when user has edit_posts.
     */
    public function testCheckEditPermissionReturnsTrueWhenUserHasCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $this->assertTrue($this->controller->testCheckEditPermission());
    }

    /**
     * Test check_edit_permission returns false when user lacks edit_posts.
     */
    public function testCheckEditPermissionReturnsFalseWhenUserLacksCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $this->assertFalse($this->controller->testCheckEditPermission());
    }

    /**
     * Test get_collection_params returns standard pagination parameters.
     */
    public function testGetCollectionParamsReturnsPageParameter(): void
    {
        $params = $this->controller->testGetCollectionParams();

        $this->assertArrayHasKey('page', $params);
        $this->assertEquals(1, $params['page']['default']);
        $this->assertEquals(1, $params['page']['minimum']);
    }

    /**
     * Test get_collection_params returns per_page parameter.
     */
    public function testGetCollectionParamsReturnsPerPageParameter(): void
    {
        $params = $this->controller->testGetCollectionParams();

        $this->assertArrayHasKey('per_page', $params);
        $this->assertEquals(10, $params['per_page']['default']);
        $this->assertEquals(1, $params['per_page']['minimum']);
        $this->assertEquals(100, $params['per_page']['maximum']);
    }

    /**
     * Test prepare_pagination_response adds X-WP-Total header.
     */
    public function testPreparePaginationResponseAddsXWpTotalHeader(): void
    {
        $response = new \WP_REST_Response(['items' => []]);
        $total = 42;
        $perPage = 10;

        $result = $this->controller->testPreparePaginationResponse($response, $total, $perPage);

        $headers = $result->get_headers();
        $this->assertArrayHasKey('X-WP-Total', $headers);
        $this->assertEquals(42, $headers['X-WP-Total']);
    }

    /**
     * Test prepare_pagination_response adds X-WP-TotalPages header.
     */
    public function testPreparePaginationResponseAddsXWpTotalPagesHeader(): void
    {
        $response = new \WP_REST_Response(['items' => []]);
        $total = 42;
        $perPage = 10;

        $result = $this->controller->testPreparePaginationResponse($response, $total, $perPage);

        $headers = $result->get_headers();
        $this->assertArrayHasKey('X-WP-TotalPages', $headers);
        $this->assertEquals(5, $headers['X-WP-TotalPages']); // ceil(42/10) = 5
    }

    /**
     * Test prepare_pagination_response calculates total pages correctly with exact division.
     */
    public function testPreparePaginationResponseCalculatesTotalPagesWithExactDivision(): void
    {
        $response = new \WP_REST_Response(['items' => []]);
        $total = 30;
        $perPage = 10;

        $result = $this->controller->testPreparePaginationResponse($response, $total, $perPage);

        $headers = $result->get_headers();
        $this->assertEquals(3, $headers['X-WP-TotalPages']);
    }

    /**
     * Test prepare_pagination_response handles zero items.
     */
    public function testPreparePaginationResponseHandlesZeroItems(): void
    {
        $response = new \WP_REST_Response(['items' => []]);
        $total = 0;
        $perPage = 10;

        $result = $this->controller->testPreparePaginationResponse($response, $total, $perPage);

        $headers = $result->get_headers();
        $this->assertEquals(0, $headers['X-WP-Total']);
        $this->assertEquals(0, $headers['X-WP-TotalPages']);
    }

    /**
     * Test prepare_item_response wraps item in standard format.
     */
    public function testPrepareItemResponseWrapsItemInData(): void
    {
        $item = ['id' => 1, 'name' => 'Test'];

        $result = $this->controller->testPrepareItemResponse($item);

        $this->assertEquals($item, $result);
    }

    /**
     * Test prepare_error_response returns WP_Error with correct message.
     */
    public function testPrepareErrorResponseReturnsWpErrorWithMessage(): void
    {
        $error = $this->controller->testPrepareErrorResponse('Something went wrong', 404);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertEquals('Something went wrong', $error->get_error_message());
    }

    /**
     * Test prepare_error_response returns WP_Error with correct code.
     */
    public function testPrepareErrorResponseReturnsWpErrorWithCode(): void
    {
        $error = $this->controller->testPrepareErrorResponse('Not found', 404);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $errorData = $error->get_error_data();
        $this->assertEquals(404, $errorData['status']);
    }

    /**
     * Test prepare_error_response with 400 status.
     */
    public function testPrepareErrorResponseWith400Status(): void
    {
        $error = $this->controller->testPrepareErrorResponse('Bad request', 400);

        $errorData = $error->get_error_data();
        $this->assertEquals(400, $errorData['status']);
    }

    /**
     * Test prepare_error_response with 403 status.
     */
    public function testPrepareErrorResponseWith403Status(): void
    {
        $error = $this->controller->testPrepareErrorResponse('Forbidden', 403);

        $errorData = $error->get_error_data();
        $this->assertEquals(403, $errorData['status']);
    }

    /**
     * Test prepare_error_response with 500 status.
     */
    public function testPrepareErrorResponseWith500Status(): void
    {
        $error = $this->controller->testPrepareErrorResponse('Server error', 500);

        $errorData = $error->get_error_data();
        $this->assertEquals(500, $errorData['status']);
    }
}
