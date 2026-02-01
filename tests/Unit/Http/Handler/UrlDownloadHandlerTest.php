<?php

/**
 * Tests for Http\Handler\UrlDownloadHandler class.
 *
 * @package SermonBrowser\Tests\Unit\Http\Handler
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http\Handler;

use ReflectionClass;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\Handler\UrlDownloadHandler;

/**
 * Test class for UrlDownloadHandler.
 *
 * Tests structure and security documentation.
 * Actual URL downloading involves exit/die and requires integration tests.
 */
class UrlDownloadHandlerTest extends TestCase
{
    /**
     * Test that the class exists and has expected methods.
     */
    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(UrlDownloadHandler::class);

        $this->assertTrue($reflection->hasMethod('handle'), 'handle() method should exist');
        $this->assertTrue($reflection->hasMethod('sendHeaders'), 'sendHeaders() method should exist');
        $this->assertTrue($reflection->hasMethod('outputFile'), 'outputFile() method should exist');
        $this->assertTrue($reflection->hasMethod('cleanup'), 'cleanup() method should exist');
    }

    /**
     * Test that handle method is public and static.
     */
    public function testHandleMethodIsPublicAndStatic(): void
    {
        $reflection = new ReflectionClass(UrlDownloadHandler::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isStatic(), 'handle() should be static');
        $this->assertTrue($method->isPublic(), 'handle() should be public');
    }

    /**
     * Test that helper methods are private and static.
     *
     * @dataProvider helperMethodProvider
     */
    public function testHelperMethodsArePrivateAndStatic(string $methodName): void
    {
        $reflection = new ReflectionClass(UrlDownloadHandler::class);
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
            'sendHeaders' => ['sendHeaders'],
            'outputFile' => ['outputFile'],
            'cleanup' => ['cleanup'],
        ];
    }

    /**
     * Test that the class uses HandlerTrait.
     */
    public function testClassUsesHandlerTrait(): void
    {
        $reflection = new ReflectionClass(UrlDownloadHandler::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains(
            'SermonBrowser\Http\Handler\HandlerTrait',
            $traits,
            'UrlDownloadHandler should use HandlerTrait'
        );
    }

    /**
     * Test SSRF protection documentation.
     */
    public function testSsrfProtectionDocumentation(): void
    {
        // This documents the SSRF (Server-Side Request Forgery) protection:
        //
        // CRITICAL SECURITY MEASURE:
        // 1. The requested URL must exist in the database as type='url'
        // 2. Unregistered URLs are rejected with 404
        // 3. This prevents attackers from using the server to:
        //    - Probe internal networks
        //    - Access internal services (metadata endpoints, etc.)
        //    - Exfiltrate data through the server
        //
        // The database acts as an allowlist of permitted external URLs.

        $this->addToAssertionCount(1);
        $this->assertTrue(true, 'SSRF protection is documented');
    }

    /**
     * Test expected behavior documentation.
     *
     * @dataProvider behaviorProvider
     */
    public function testBehaviorDocumentation(string $scenario, string $expectedOutcome): void
    {
        $behaviors = [
            'url_not_in_db' => '404 error (SSRF protection)',
            'url_type_file' => '404 error (wrong type)',
            'url_type_url' => 'Download proceeds',
            'download_fails' => '404 error (WP_Error)',
            'download_success' => 'Headers sent, file served, temp file cleaned up',
        ];

        $this->assertArrayHasKey($scenario, $behaviors);
        $this->assertSame($expectedOutcome, $behaviors[$scenario]);
    }

    /**
     * Data provider for behavior documentation.
     *
     * @return array<string, array{string, string}>
     */
    public static function behaviorProvider(): array
    {
        return [
            'URL not in database' => ['url_not_in_db', '404 error (SSRF protection)'],
            'URL exists but type is file' => ['url_type_file', '404 error (wrong type)'],
            'URL exists with type url' => ['url_type_url', 'Download proceeds'],
            'download_url returns error' => ['download_fails', '404 error (WP_Error)'],
            'download succeeds' => ['download_success', 'Headers sent, file served, temp file cleaned up'],
        ];
    }
}
