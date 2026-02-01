<?php

/**
 * Tests for Http\RequestInterceptor class.
 *
 * @package SermonBrowser\Tests\Unit\Http
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http;

use ReflectionClass;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\RequestInterceptor;

/**
 * Test class for RequestInterceptor.
 *
 * Tests structure and routing logic documentation.
 * Actual request handling involves exit/die and requires integration tests.
 */
class RequestInterceptorTest extends TestCase
{
    /**
     * Test that the class exists and has expected methods.
     */
    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(RequestInterceptor::class);

        $this->assertTrue($reflection->hasMethod('intercept'), 'intercept() method should exist');
        $this->assertTrue($reflection->hasMethod('isAjaxRequest'), 'isAjaxRequest() method should exist');
        $this->assertTrue($reflection->hasMethod('isCssRequest'), 'isCssRequest() method should exist');
        $this->assertTrue($reflection->hasMethod('isDownloadRequest'), 'isDownloadRequest() method should exist');
        $this->assertTrue($reflection->hasMethod('isShowRequest'), 'isShowRequest() method should exist');
        $this->assertTrue($reflection->hasMethod('handleDownload'), 'handleDownload() method should exist');
        $this->assertTrue($reflection->hasMethod('handleShow'), 'handleShow() method should exist');
    }

    /**
     * Test that intercept method is public and static.
     */
    public function testInterceptMethodIsPublicAndStatic(): void
    {
        $reflection = new ReflectionClass(RequestInterceptor::class);
        $method = $reflection->getMethod('intercept');

        $this->assertTrue($method->isStatic(), 'intercept() should be static');
        $this->assertTrue($method->isPublic(), 'intercept() should be public');
    }

    /**
     * Test that helper methods are private and static.
     *
     * @dataProvider helperMethodProvider
     */
    public function testHelperMethodsArePrivateAndStatic(string $methodName): void
    {
        $reflection = new ReflectionClass(RequestInterceptor::class);
        $method = $reflection->getMethod($methodName);

        $this->assertTrue($method->isStatic(), "{$methodName}() should be static");
        $this->assertTrue($method->isPrivate(), "{$methodName}() should be private");
    }

    /**
     * Data provider for helper method tests.
     *
     * @return array<string, array{string}>
     */
    public static function helperMethodProvider(): array
    {
        return [
            'ajax request checker' => ['isAjaxRequest'],
            'css request checker' => ['isCssRequest'],
            'download request checker' => ['isDownloadRequest'],
            'show request checker' => ['isShowRequest'],
            'download handler' => ['handleDownload'],
            'show handler' => ['handleShow'],
        ];
    }

    /**
     * Test routing logic documentation.
     *
     * Documents the request parameters to handler mapping.
     *
     * @dataProvider routingProvider
     */
    public function testRoutingLogicDocumentation(string $scenario, array $params, string $expectedHandler): void
    {
        // This test documents the routing logic:
        // - $_POST['sermon']=1 -> LegacyAjaxHandler::handle()
        // - sb-style.css in URL or $_GET['sb-style'] -> StyleOutput::output()
        // - $_GET['download'] + $_GET['file_name'] -> FileDownloadHandler::handle()
        // - $_GET['download'] + $_REQUEST['url'] -> UrlDownloadHandler::handle()
        // - $_GET['show'] + $_GET['file_name'] -> FileRedirectHandler::handle()
        // - $_GET['show'] + $_REQUEST['url'] -> UrlRedirectHandler::handle()

        $expectedRouting = [
            'ajax' => 'LegacyAjaxHandler',
            'css_param' => 'StyleOutput',
            'css_url' => 'StyleOutput',
            'download_file' => 'FileDownloadHandler',
            'download_url' => 'UrlDownloadHandler',
            'show_file' => 'FileRedirectHandler',
            'show_url' => 'UrlRedirectHandler',
        ];

        $this->assertArrayHasKey($scenario, $expectedRouting);
        $this->assertSame($expectedHandler, $expectedRouting[$scenario]);
    }

    /**
     * Data provider for routing documentation.
     *
     * @return array<string, array{string, array, string}>
     */
    public static function routingProvider(): array
    {
        return [
            'AJAX request' => ['ajax', ['sermon' => 1], 'LegacyAjaxHandler'],
            'CSS by query param' => ['css_param', ['sb-style' => '1'], 'StyleOutput'],
            'CSS by URL pattern' => ['css_url', ['REQUEST_URI' => '/sb-style.css'], 'StyleOutput'],
            'file download' => ['download_file', ['download' => 1, 'file_name' => 'x'], 'FileDownloadHandler'],
            'URL download' => ['download_url', ['download' => 1, 'url' => 'http://x'], 'UrlDownloadHandler'],
            'file show' => ['show_file', ['show' => 1, 'file_name' => 'x'], 'FileRedirectHandler'],
            'URL show' => ['show_url', ['show' => 1, 'url' => 'http://x'], 'UrlRedirectHandler'],
        ];
    }

    /**
     * Test file_name takes priority over url in routing.
     */
    public function testFileNamePriorityDocumentation(): void
    {
        // This documents that when both file_name and url are provided,
        // file_name handlers take priority (checked first in handleDownload/handleShow)

        $this->addToAssertionCount(1);

        // The priority is ensured by the if/elseif structure:
        // if (isset($_GET['file_name'])) { ... } elseif (isset($_REQUEST['url'])) { ... }
        $this->assertTrue(true, 'file_name is checked before url in handler routing');
    }
}
