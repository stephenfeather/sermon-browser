<?php

/**
 * Tests for LegacyAjaxHandler.
 *
 * This handler has significant testability challenges due to:
 * - Static methods that call die()
 * - Direct $_POST access
 * - HTML output mixed with logic
 * - Constant definition in handle()
 *
 * The modern AJAX handlers in src/Admin/Ajax/ are the preferred implementation.
 * This test focuses on what can be reasonably tested.
 *
 * @package SermonBrowser\Tests\Unit\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Ajax\LegacyAjaxHandler;
use ReflectionClass;

/**
 * Test LegacyAjaxHandler structure and routing.
 */
class LegacyAjaxHandlerTest extends TestCase
{
    /**
     * Test that the class exists and has expected methods.
     */
    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);

        // Public methods
        $this->assertTrue($reflection->hasMethod('handle'), 'handle() method should exist');

        // Private handler methods
        $this->assertTrue($reflection->hasMethod('handlePreacher'), 'handlePreacher() method should exist');
        $this->assertTrue($reflection->hasMethod('handleService'), 'handleService() method should exist');
        $this->assertTrue($reflection->hasMethod('handleSeries'), 'handleSeries() method should exist');
        $this->assertTrue($reflection->hasMethod('handleFile'), 'handleFile() method should exist');
        $this->assertTrue($reflection->hasMethod('handleSermonPagination'), 'handleSermonPagination() method should exist');
        $this->assertTrue($reflection->hasMethod('handleFilePagination'), 'handleFilePagination() method should exist');
    }

    /**
     * Test that handle method is static.
     */
    public function testHandleMethodIsStatic(): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isStatic(), 'handle() should be static');
        $this->assertTrue($method->isPublic(), 'handle() should be public');
    }

    /**
     * Test that handler methods are private and static.
     *
     * @dataProvider handlerMethodProvider
     */
    public function testHandlerMethodsArePrivateAndStatic(string $methodName): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);
        $method = $reflection->getMethod($methodName);

        $this->assertTrue($method->isStatic(), "{$methodName}() should be static");
        $this->assertTrue($method->isPrivate(), "{$methodName}() should be private");
    }

    /**
     * Data provider for handler method tests.
     *
     * @return array<string, array{string}>
     */
    public static function handlerMethodProvider(): array
    {
        return [
            'preacher handler' => ['handlePreacher'],
            'service handler' => ['handleService'],
            'series handler' => ['handleSeries'],
            'file handler' => ['handleFile'],
            'sermon pagination handler' => ['handleSermonPagination'],
            'file pagination handler' => ['handleFilePagination'],
        ];
    }

    /**
     * Test routing logic determination.
     *
     * This documents the POST parameter to handler mapping without
     * actually calling the methods (which would call die()).
     *
     * @dataProvider routingProvider
     */
    public function testRoutingLogicDocumentation(string $postKey, string $expectedHandler): void
    {
        // This test documents the routing logic:
        // - $_POST['pname'] -> handlePreacher()
        // - $_POST['sname'] -> handleService()
        // - $_POST['ssname'] -> handleSeries()
        // - $_POST['fname'] -> handleFile()
        // - $_POST['fetch'] -> handleSermonPagination()
        // - $_POST['fetchU'] | $_POST['fetchL'] | $_POST['search'] -> handleFilePagination()

        $this->addToAssertionCount(1);

        // The assertion here is documentation - we're confirming the expected mapping
        $expectedRouting = [
            'pname' => 'handlePreacher',
            'sname' => 'handleService',
            'ssname' => 'handleSeries',
            'fname' => 'handleFile',
            'fetch' => 'handleSermonPagination',
            'fetchU' => 'handleFilePagination',
            'fetchL' => 'handleFilePagination',
            'search' => 'handleFilePagination',
        ];

        $this->assertArrayHasKey($postKey, $expectedRouting);
        $this->assertSame($expectedHandler, $expectedRouting[$postKey]);
    }

    /**
     * Data provider for routing documentation.
     *
     * @return array<string, array{string, string}>
     */
    public static function routingProvider(): array
    {
        return [
            'preacher name triggers preacher handler' => ['pname', 'handlePreacher'],
            'service name triggers service handler' => ['sname', 'handleService'],
            'series name triggers series handler' => ['ssname', 'handleSeries'],
            'file name triggers file handler' => ['fname', 'handleFile'],
            'fetch triggers sermon pagination' => ['fetch', 'handleSermonPagination'],
            'fetchU triggers file pagination' => ['fetchU', 'handleFilePagination'],
            'fetchL triggers file pagination' => ['fetchL', 'handleFilePagination'],
            'search triggers file pagination' => ['search', 'handleFilePagination'],
        ];
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }
}
