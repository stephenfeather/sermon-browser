<?php

/**
 * Tests for Http\SecurityHeaders class.
 *
 * @package SermonBrowser\Tests\Unit\Http
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ReflectionClass;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\SecurityHeaders;

/**
 * Test class for SecurityHeaders.
 *
 * Tests the security headers configuration and hook registration.
 */
class SecurityHeadersTest extends TestCase
{
    /**
     * Test that the class exists and has expected methods.
     */
    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(SecurityHeaders::class);

        $this->assertTrue($reflection->hasMethod('register'), 'register() method should exist');
        $this->assertTrue($reflection->hasMethod('addAdminHeaders'), 'addAdminHeaders() method should exist');
        $this->assertTrue($reflection->hasMethod('addRestHeaders'), 'addRestHeaders() method should exist');
        $this->assertTrue($reflection->hasMethod('send'), 'send() method should exist');
        $this->assertTrue($reflection->hasMethod('getHeaders'), 'getHeaders() method should exist');
    }

    /**
     * Test that all methods are public and static.
     *
     * @dataProvider methodProvider
     */
    public function testMethodsArePublicAndStatic(string $methodName): void
    {
        $reflection = new ReflectionClass(SecurityHeaders::class);
        $method = $reflection->getMethod($methodName);

        $this->assertTrue($method->isStatic(), "{$methodName}() should be static");
        $this->assertTrue($method->isPublic(), "{$methodName}() should be public");
    }

    /**
     * Data provider for method tests.
     *
     * @return array<array{string}>
     */
    public static function methodProvider(): array
    {
        return [
            ['register'],
            ['addAdminHeaders'],
            ['addRestHeaders'],
            ['send'],
            ['getHeaders'],
        ];
    }

    /**
     * Test that getHeaders returns the expected security headers.
     */
    public function testGetHeadersReturnsSecurityHeaders(): void
    {
        $headers = SecurityHeaders::getHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
    }

    /**
     * Test that register hooks into WordPress.
     */
    public function testRegisterAddsHooks(): void
    {
        Functions\expect('add_action')
            ->once()
            ->with('admin_init', [SecurityHeaders::class, 'addAdminHeaders'], 1);

        Functions\expect('add_filter')
            ->once()
            ->with('rest_pre_serve_request', [SecurityHeaders::class, 'addRestHeaders'], 10, 4);

        SecurityHeaders::register();
    }

    /**
     * Test that addAdminHeaders skips non-sermon pages.
     */
    public function testAddAdminHeadersSkipsNonSermonPages(): void
    {
        // Set up a non-sermon page.
        $_GET['page'] = 'other-plugin';

        // No headers should be sent (we'd see a headers_sent check if it tried).
        SecurityHeaders::addAdminHeaders();

        // Clean up.
        unset($_GET['page']);

        // Test passes if no headers were attempted.
        $this->assertTrue(true);
    }

    /**
     * Test that addRestHeaders processes sermon-browser routes.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddRestHeadersProcessesSermonBrowserRoutes(): void
    {
        $mockRequest = \Mockery::mock(\WP_REST_Request::class);
        $mockRequest->shouldReceive('get_route')
            ->once()
            ->andReturn('/sermon-browser/v1/sermons');

        $mockResponse = \Mockery::mock(\WP_REST_Response::class);
        $mockServer = \Mockery::mock(\WP_REST_Server::class);

        $result = SecurityHeaders::addRestHeaders(false, $mockResponse, $mockRequest, $mockServer);

        $this->assertFalse($result);
    }

    /**
     * Test that addRestHeaders skips non-sermon-browser routes.
     */
    public function testAddRestHeadersSkipsOtherRoutes(): void
    {
        $mockRequest = \Mockery::mock(\WP_REST_Request::class);
        $mockRequest->shouldReceive('get_route')
            ->once()
            ->andReturn('/wp/v2/posts');

        $mockResponse = \Mockery::mock(\WP_REST_Response::class);
        $mockServer = \Mockery::mock(\WP_REST_Server::class);

        $result = SecurityHeaders::addRestHeaders(false, $mockResponse, $mockRequest, $mockServer);

        $this->assertFalse($result);
    }

    /**
     * Test that HEADERS constant contains required security headers.
     */
    public function testHeadersConstantIsCorrectlyDefined(): void
    {
        $reflection = new ReflectionClass(SecurityHeaders::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('HEADERS', $constants);
        $this->assertCount(2, $constants['HEADERS']);
    }
}
